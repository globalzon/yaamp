
#include "stratum.h"

//void check_job(YAAMP_JOB *job)
//{
//	if(job->coind && job->remote)
//	{
//		debuglog("error memory\n");
//	}
//}

static YAAMP_WORKER *share_find_worker(YAAMP_CLIENT *client, YAAMP_JOB *job, bool valid)
{
	for(CLI li = g_list_worker.first; li; li = li->next)
	{
		YAAMP_WORKER *worker = (YAAMP_WORKER *)li->data;
		if(worker->deleted) continue;

		if(	worker->userid == client->userid &&
			worker->workerid == client->workerid &&
			worker->valid == valid)
		{
			if(!job && !worker->coinid && !worker->remoteid)
				return worker;

			else if(!job)
				continue;

			else if((job->coind && worker->coinid == job->coind->id) ||
				(job->remote && worker->remoteid == job->remote->id))
				return worker;
		}
	}

	return NULL;
}

static void share_add_worker(YAAMP_CLIENT *client, YAAMP_JOB *job, bool valid, int error_number)
{
//	check_job(job);
	g_list_worker.Enter();

	YAAMP_WORKER *worker = share_find_worker(client, job, valid);
	if(!worker)
	{
		worker = new YAAMP_WORKER;
		memset(worker, 0, sizeof(YAAMP_WORKER));

		worker->userid = client->userid;
		worker->workerid = client->workerid;
		worker->coinid = job? (job->coind? job->coind->id: 0): 0;
		worker->remoteid = job? (job->remote? job->remote->id: 0): 0;
		worker->valid = valid;
		worker->error_number = error_number;

		if(g_stratum_reconnect)
			worker->extranonce1 = !client->reconnecting && (client->reconnectable || client->extranonce_subscribe);
		else
			worker->extranonce1 = client->extranonce_subscribe;

		g_list_worker.AddTail(worker);
	}

	if(valid)
	{
		worker->difficulty += client->difficulty_actual / g_current_algo->diff_multiplier;
		client->speed += client->difficulty_actual / g_current_algo->diff_multiplier * 42;
	//	client->source->speed += client->difficulty_actual / g_current_algo->diff_multiplier * 42;
	}

	g_list_worker.Leave();
}

/////////////////////////////////////////////////////////////////////////

void share_add(YAAMP_CLIENT *client, YAAMP_JOB *job, bool valid, char *extranonce2, char *ntime, char *nonce, int error_number)
{
//	check_job(job);
	share_add_worker(client, job, valid, error_number);

	YAAMP_SHARE *share = new YAAMP_SHARE;
	memset(share, 0, sizeof(YAAMP_SHARE));

	share->jobid = job? job->id: 0;
	strcpy(share->extranonce2, extranonce2);
	strcpy(share->ntime, ntime);
	strcpy(share->nonce, nonce);
	strcpy(share->nonce1, client->extranonce1);

	g_list_share.AddTail(share);
}

YAAMP_SHARE *share_find(int jobid, char *extranonce2, char *ntime, char *nonce, char *nonce1)
{
	g_list_share.Enter();
	for(CLI li = g_list_share.first; li; li = li->next)
	{
		YAAMP_SHARE *share = (YAAMP_SHARE *)li->data;
		if(share->deleted) continue;

		if(	share->jobid == jobid &&
			!strcmp(share->extranonce2, extranonce2) && !strcmp(share->ntime, ntime) &&
			!strcmp(share->nonce, nonce) && !strcmp(share->nonce1, nonce1))
		{
			g_list_share.Leave();
			return share;
		}
	}

	g_list_share.Leave();
	return NULL;
}

void share_write(YAAMP_DB *db)
{
	int pid = getpid();
	int count = 0;
	int now = time(NULL);

	char buffer[1024*1024] = "insert into shares (userid, workerid, coinid, jobid, pid, valid, extranonce1, difficulty, time, algo, error) values ";
	g_list_worker.Enter();

	for(CLI li = g_list_worker.first; li; li = li->next)
	{
		YAAMP_WORKER *worker = (YAAMP_WORKER *)li->data;
		if(worker->deleted) continue;

		if(count) strcat(buffer, ",");
		sprintf(buffer+strlen(buffer), "(%d, %d, %d, %d, %d, %d, %d, %f, %d, '%s', %d)",
			worker->userid, worker->workerid, worker->coinid, worker->remoteid, pid,
			worker->valid, worker->extranonce1, worker->difficulty, now, g_stratum_algo, worker->error_number);

		if(++count >= 1000)
		{
			db_query(db, buffer);

			strcpy(buffer, "insert into shares (userid, workerid, coinid, jobid, pid, valid, extranonce1, difficulty, time, algo, error) values ");
			count = 0;
		}

		object_delete(worker);
	}

	g_list_worker.Leave();
	if(count) db_query(db, buffer);
}

void share_prune(YAAMP_DB *db)
{
	g_list_share.Enter();
	for(CLI li = g_list_share.first; li; li = li->next)
	{
		YAAMP_SHARE *share = (YAAMP_SHARE *)li->data;
		if(share->deleted) continue;

		YAAMP_JOB *job = (YAAMP_JOB *)object_find(&g_list_job, share->jobid);
		if(job) continue;

		object_delete(share);
	}

	g_list_share.Leave();
}

/////////////////////////////////////////////////////////////////////////////////////////////////////////////

void block_prune(YAAMP_DB *db)
{
	int count = 0;
	char buffer[128*1024] = "insert into blocks (height, blockhash, coin_id, userid, category, difficulty, difficulty_user, time, algo) values ";

	g_list_block.Enter();
	for(CLI li = g_list_block.first; li; li = li->next)
	{
		YAAMP_BLOCK *block = (YAAMP_BLOCK *)li->data;
		if(!block->confirmed)
		{
			if(block->created + 30 < time(NULL))
				object_delete(block);

			continue;
		}

		if(count) strcat(buffer, ",");
		sprintf(buffer+strlen(buffer), "(%d, '%s', %d, %d, 'new', %f, %f, %d, '%s')",
			block->height, block->hash, block->coinid, block->userid,
			block->difficulty, block->difficulty_user, (int)block->created, g_stratum_algo);

		object_delete(block);
		count++;
	}

	g_list_block.Leave();
	if(count) db_query(db, buffer);
}

void block_add(int userid, int coinid, int height, double difficulty, double difficulty_user, const char *hash1, const char *hash2)
{
	YAAMP_BLOCK *block = new YAAMP_BLOCK;
	memset(block, 0, sizeof(YAAMP_BLOCK));

	block->created = time(NULL);
	block->userid = userid;
	block->coinid = coinid;
	block->height = height;
	block->difficulty = difficulty;
	block->difficulty_user = difficulty_user;

	strcpy(block->hash1, hash1);
	strcpy(block->hash2, hash2);

	g_list_block.AddTail(block);
}

void block_confirm(int coinid, const char *hash)
{
	if(strlen(hash) > 65) return;
	for(CLI li = g_list_block.first; li; li = li->next)
	{
		YAAMP_BLOCK *block = (YAAMP_BLOCK *)li->data;
		if(block->coinid == coinid)
		{
			if(strcmp(block->hash1, hash) && strcmp(block->hash2, hash)) continue;
			debuglog("*** CONFIRMED %d\n", block->height);

			strncpy(block->hash, hash, 65);
			block->confirmed = true;

			return;
		}
	}
}

//////////////////////////////////////////////////////////////////////////////////////////

YAAMP_SUBMIT *submit_add(int remoteid, double difficulty)
{
	YAAMP_SUBMIT *submit = new YAAMP_SUBMIT;
	memset(submit, 0, sizeof(YAAMP_SUBMIT));

	submit->created = time(NULL);
	submit->valid = true;
	submit->remoteid = remoteid;
	submit->difficulty = difficulty / g_current_algo->diff_multiplier;

	g_list_submit.AddTail(submit);
	return submit;
}

void submit_prune(YAAMP_DB *db)
{
	int count = 0;
	char buffer[128*1024] = "insert into jobsubmits (jobid, valid, difficulty, time, algo, status) values ";

	g_list_submit.Enter();
	for(CLI li = g_list_submit.first; li; li = li->next)
	{
		YAAMP_SUBMIT *submit = (YAAMP_SUBMIT *)li->data;

		if(count) strcat(buffer, ",");
		sprintf(buffer+strlen(buffer), "(%d, %d, %f, %d, '%s', 0)", submit->remoteid, submit->valid,
			submit->difficulty, (int)submit->created, g_stratum_algo);

		if(++count >= 1000)
		{
			db_query(db, buffer);

			strcpy(buffer, "insert into jobsubmits (jobid, valid, difficulty, time, algo, status) values ");
			count = 0;
		}

		object_delete(submit);
	}

	g_list_submit.Leave();
	if(count) db_query(db, buffer);
}


