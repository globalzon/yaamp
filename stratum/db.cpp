
#include "stratum.h"
#include <signal.h>

void db_reconnect(YAAMP_DB *db)
{
	mysql_init(&db->mysql);
	for(int i=0; i<6; i++)
	{
		MYSQL *p = mysql_real_connect(&db->mysql, g_sql_host, g_sql_username, g_sql_password, g_sql_database, 0, 0, 0);
		if(p) break;

		stratumlog("%d, %s\n", i, mysql_error(&db->mysql));
		sleep(10);

		mysql_init(&db->mysql);
	}
}

YAAMP_DB *db_connect()
{
	YAAMP_DB *db = new YAAMP_DB;
	db_reconnect(db);

	return db;
}

void db_close(YAAMP_DB *db)
{
	mysql_close(&db->mysql);
	delete db;
}

char *db_clean_string(YAAMP_DB *db, char *string)
{
	string[1000] = 0;
	char tmp[1024];

    unsigned long ret = mysql_real_escape_string(&db->mysql, tmp, string, strlen(string));
    strcpy(string, tmp);

    return string;
}

void db_query(YAAMP_DB *db, const char *format, ...)
{
	va_list arglist;
	va_start(arglist, format);

	char *buffer = (char *)malloc(YAAMP_SMALLBUFSIZE+strlen(format));
	if(!buffer) return;

	int len = vsprintf(buffer, format, arglist);
	va_end(arglist);

	while(1)
	{
		int res = mysql_query(&db->mysql, buffer);
		if(!res) break;
		res = mysql_errno(&db->mysql);

		stratumlog("SQL ERROR: %d, %s\n", res, mysql_error(&db->mysql));
		if(res != CR_SERVER_GONE_ERROR && res != CR_SERVER_LOST) exit(1);

		db_reconnect(db);
	}

	free(buffer);
}

///////////////////////////////////////////////////////////////////////

void db_register_stratum(YAAMP_DB *db)
{
	int pid = getpid();
	int t = time(NULL);

	db_query(db, "insert into stratums (pid, time, algo) values (%d, %d, '%s') on duplicate key update time=%d",
		pid, t, g_current_algo->name, t);
}

void db_update_algos(YAAMP_DB *db)
{
	if(g_current_algo->overflow)
	{
		debuglog("setting overflow\n");
		g_current_algo->overflow = false;

		db_query(db, "update algos set overflow=true where name='%s'", g_current_algo->name);
	}

	///////////////////////////////////////////////////////////////////////////////////////////

	db_query(db, "select name, profit, rent, factor from algos");

	MYSQL_RES *result = mysql_store_result(&db->mysql);
	if(!result) return;

	MYSQL_ROW row;
	while((row = mysql_fetch_row(result)) != NULL)
	{
		YAAMP_ALGO *algo = stratum_find_algo(row[0]);
		if(!algo) continue;

		if(row[1]) algo->profit = atof(row[1]);
		if(row[2]) algo->rent = atof(row[2]);
		if(row[3]) algo->factor = atof(row[3]);
	}

	mysql_free_result(result);

	////////////////////

	g_list_client.Enter();
	for(CLI li = g_list_client.first; li; li = li->next)
	{
		YAAMP_CLIENT *client = (YAAMP_CLIENT *)li->data;
		if(client->deleted) continue;

		client_reset_multialgo(client, false);
	}

	g_list_client.Leave();
}

////////////////////////////////////////////////////////////////////////////////

void db_update_coinds(YAAMP_DB *db)
{
	for(CLI li = g_list_coind.first; li; li = li->next)
	{
		YAAMP_COIND *coind = (YAAMP_COIND *)li->data;
		if(coind->deleted) continue;
		if(coind->auto_ready) continue;

		debuglog("disabling %s\n", coind->symbol);
		db_query(db, "update coins set auto_ready=%d where id=%d", coind->auto_ready, coind->id);
	}

	////////////////////////////////////////////////////////////////////////////////////////

	db_query(db, "select id, name, rpchost, rpcport, rpcuser, rpcpasswd, rpcencoding, master_wallet, reward, price, "\
		"hassubmitblock, txmessage, enable, auto_ready, algo, pool_ttf, charity_address, charity_amount, charity_percent, "\
		"reward_mul, symbol, auxpow, actual_ttf, network_ttf, usememorypool "\
		"from coins where enable and auto_ready and algo='%s' order by index_avg", g_stratum_algo);

	MYSQL_RES *result = mysql_store_result(&db->mysql);
	if(!result) yaamp_error("Cant query database");

	MYSQL_ROW row;
	g_list_coind.Enter();

	while((row = mysql_fetch_row(result)) != NULL)
	{
		YAAMP_COIND *coind = (YAAMP_COIND *)object_find(&g_list_coind, atoi(row[0]));
		if(!coind)
		{
			coind = new YAAMP_COIND;
			memset(coind, 0, sizeof(YAAMP_COIND));

			coind->newcoind = true;
			coind->newblock = true;
			coind->id = atoi(row[0]);
			coind->aux.coind = coind;
		}
		else
			coind->newcoind = false;

		strcpy(coind->name, row[1]);

		if(row[7]) strcpy(coind->wallet, row[7]);
		if(row[6]) coind->pos = strcmp(row[6], "POS")? false: true;
		if(row[10]) coind->hassubmitblock = atoi(row[10]);

		if(row[2]) strcpy(coind->rpc.host, row[2]);
		if(row[3]) coind->rpc.port = atoi(row[3]);

		if(row[4] && row[5])
		{
			char buffer[1024];
			sprintf(buffer, "%s:%s", row[4], row[5]);

			base64_encode(coind->rpc.credential, buffer);
			coind->rpc.coind = coind;
		}

		if(row[8]) coind->reward = atof(row[8]);
		if(row[9]) coind->price = atof(row[9]);
		if(row[11]) coind->txmessage = atoi(row[11]);
		if(row[12]) coind->enable = atoi(row[12]);
		if(row[13]) coind->auto_ready = atoi(row[13]);
		if(row[15]) coind->pool_ttf = atoi(row[15]);

		if(row[16]) strcpy(coind->charity_address, row[16]);
		if(row[17]) coind->charity_amount = atof(row[17]);
		if(row[18]) coind->charity_percent = atof(row[18]);
		if(row[19]) coind->reward_mul = atof(row[19]);

		strcpy(coind->symbol, row[20]);
		if(row[21]) coind->isaux = atoi(row[21]);

		if(row[22] && row[23]) coind->actual_ttf = min(atoi(row[22]), atoi(row[23]));
		else if(row[22]) coind->actual_ttf = atoi(row[22]);
		coind->actual_ttf = min(coind->actual_ttf, 120);
		coind->actual_ttf = max(coind->actual_ttf, 20);

		if(row[24]) coind->usememorypool = atoi(row[24]);

		////////////////////////////////////////////////////////////////////////////////////////////////////

		coind->touch = true;
		if(coind->newcoind)
		{
			debuglog("connecting to coind %s\n", coind->symbol);

			bool b = rpc_connect(&coind->rpc);
			coind_init(coind);

			g_list_coind.AddTail(coind);
			usleep(100*YAAMP_MS);
		}

		coind_create_job(coind);
	}

	mysql_free_result(result);

	for(CLI li = g_list_coind.first; li; li = li->next)
	{
		YAAMP_COIND *coind = (YAAMP_COIND *)li->data;
		if(coind->deleted) continue;

		if(!coind->touch)
		{
			debuglog("remove coind %s\n", coind->name);

			rpc_close(&coind->rpc);
			object_delete(coind);

			continue;
		}

		coind->touch = false;
	}

	coind_sort();
	g_list_coind.Leave();
}

///////////////////////////////////////////////////////////////////////////////////////////////

void db_update_remotes(YAAMP_DB *db)
{
	db_query(db, "select id, speed/1000000, host, port, username, password, time, price, renterid from jobs where active and ready and algo='%s' order by time", g_stratum_algo);

	MYSQL_RES *result = mysql_store_result(&db->mysql);
	if(!result) yaamp_error("Cant query database");

	MYSQL_ROW row;

	g_list_remote.Enter();
	while((row = mysql_fetch_row(result)) != NULL)
	{
		if(!row[0] || !row[1] || !row[2] || !row[3] || !row[4] || !row[5] || !row[6] || !row[7]) continue;
		bool newremote = false;

		YAAMP_REMOTE *remote = (YAAMP_REMOTE *)object_find(&g_list_remote, atoi(row[0]));
		if(!remote)
		{
			remote = new YAAMP_REMOTE;
			memset(remote, 0, sizeof(YAAMP_REMOTE));

			remote->id = atoi(row[0]);
			newremote = true;
		}

//		else if(remote->reset_balance)
//			continue;

		else if(row[6] && atoi(row[6]) > remote->updated)
			remote->status = YAAMP_REMOTE_RESET;

		remote->speed = atof(row[1]);
		strcpy(remote->host, row[2]);
		remote->port = atoi(row[3]);
		strcpy(remote->username, row[4]);
		strcpy(remote->password, row[5]);
		remote->updated = atoi(row[6]);
		remote->price = atof(row[7]);
		remote->touch = true;
		remote->submit_last = NULL;

		int renterid = row[8]? atoi(row[8]): 0;
		if(renterid && !remote->renter)
			remote->renter = (YAAMP_RENTER *)object_find(&g_list_renter, renterid);

		if(newremote)
		{
			if(remote->renter && remote->renter->balance <= 0.00001000)
			{
				debuglog("dont load that job %d\n", remote->id);
				delete remote;
				continue;
			}

			pthread_t thread;

			pthread_create(&thread, NULL, remote_thread, remote);
			pthread_detach(thread);

			g_list_remote.AddTail(remote);
			usleep(100*YAAMP_MS);
		}

		if(remote->renter)
		{
			if(!strcmp(g_current_algo->name, "sha256"))
				remote->speed = min(remote->speed, max(remote->renter->balance/g_current_algo->rent*100000000, 1));
			else
				remote->speed = min(remote->speed, max(remote->renter->balance/g_current_algo->rent*100000, 1));
		}
	}

	mysql_free_result(result);

	///////////////////////////////////////////////////////////////////////////////////////////

	for(CLI li = g_list_remote.first; li; li = li->next)
	{
		YAAMP_REMOTE *remote = (YAAMP_REMOTE *)li->data;
//		if(remote->reset_balance && remote->renter)
//		{
//			db_query(db, "update renters set balance=0 where id=%d", remote->renter->id);
//			db_query(db, "update jobs set ready=false, active=false where renterid=%d", remote->renter->id);
//
//			remote->reset_balance = false;
//		}

		if(remote->deleted) continue;

		if(remote->kill)
		{
			debuglog("******* kill that sucka %s\n", remote->host);

			pthread_cancel(remote->thread);
			object_delete(remote);

			continue;
		}

		if(remote->sock && remote->sock->last_read && remote->sock->last_read+120<time(NULL))
		{
			debuglog("****** timeout %s\n", remote->host);

			remote->status = YAAMP_REMOTE_TERMINATE;
			remote->kill = true;

			remote_close(remote);
			continue;
		}

		if(!remote->touch)
		{
			remote->status = YAAMP_REMOTE_TERMINATE;
			continue;
		}

		remote->touch = false;

		if(remote->difficulty_written != remote->difficulty_actual)
		{
			remote->difficulty_written = remote->difficulty_actual;
			db_query(db, "update jobs set difficulty=%f where id=%d", remote->difficulty_actual, remote->id);
		}
	}

//	remote_sort();
	g_list_remote.Leave();
}

void db_update_renters(YAAMP_DB *db)
{
	db_query(db, "select id, balance, updated from renters");

	MYSQL_RES *result = mysql_store_result(&db->mysql);
	if(!result) yaamp_error("Cant query database");

	MYSQL_ROW row;
	g_list_renter.Enter();

	while((row = mysql_fetch_row(result)) != NULL)
	{
		if(!row[0] || !row[1]) continue;

		YAAMP_RENTER *renter = (YAAMP_RENTER *)object_find(&g_list_renter, atoi(row[0]));
		if(!renter)
		{
			renter = new YAAMP_RENTER;
			memset(renter, 0, sizeof(YAAMP_RENTER));

			renter->id = atoi(row[0]);
			g_list_renter.AddTail(renter);
		}

		if(row[1]) renter->balance = atof(row[1]);
		if(row[2]) renter->updated = atoi(row[2]);
	}

	mysql_free_result(result);
	g_list_renter.Leave();
}







