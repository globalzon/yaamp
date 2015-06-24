
#include "stratum.h"

//#define DONTSUBMIT

void build_submit_values(YAAMP_JOB_VALUES *submitvalues, YAAMP_JOB_TEMPLATE *templ,
	const char *nonce1, const char *nonce2, const char *ntime, const char *nonce)
{
	sprintf(submitvalues->coinbase, "%s%s%s%s", templ->coinb1, nonce1, nonce2, templ->coinb2);
	int coinbase_len = strlen(submitvalues->coinbase);

	unsigned char coinbase_bin[1024];
	memset(coinbase_bin, 0, 1024);
	binlify(coinbase_bin, submitvalues->coinbase);

	char doublehash[128];
	memset(doublehash, 0, 128);
	sha256_double_hash_hex((char *)coinbase_bin, doublehash, coinbase_len/2);

	string merkleroot = merkle_with_first(templ->txsteps, doublehash);
	ser_string_be(merkleroot.c_str(), submitvalues->merkleroot_be, 8);

	sprintf(submitvalues->header, "%s%s%s%s%s%s", templ->version, templ->prevhash_be, submitvalues->merkleroot_be,
		ntime, templ->nbits, nonce);

	ser_string_be(submitvalues->header, submitvalues->header_be, 20);
	binlify(submitvalues->header_bin, submitvalues->header_be);

//	printf("%s\n", submitvalues->header_be);
	int header_len = strlen(submitvalues->header)/2;
	g_current_algo->hash_function((char *)submitvalues->header_bin, (char *)submitvalues->hash_bin, header_len);

	hexlify(submitvalues->hash_hex, submitvalues->hash_bin, 32);
	string_be(submitvalues->hash_hex, submitvalues->hash_be);
}

/////////////////////////////////////////////////////////////////////////////////

void client_do_submit(YAAMP_CLIENT *client, YAAMP_JOB *job, YAAMP_JOB_VALUES *submitvalues, char *extranonce2, char *ntime, char *nonce)
{
	YAAMP_COIND *coind = job->coind;
	YAAMP_JOB_TEMPLATE *templ = job->templ;

	if(job->block_found) return;
	if(job->deleted) return;

	uint64_t hash_int = get_hash_difficulty(submitvalues->hash_bin);
	uint64_t coin_target = decode_compact(templ->nbits);

	int block_size = YAAMP_SMALLBUFSIZE;
	vector<string>::const_iterator i;

	for(i = templ->txdata.begin(); i != templ->txdata.end(); ++i)
		block_size += strlen((*i).c_str());

	char *block_hex = (char *)malloc(block_size);
	if(!block_hex) return;

	// do aux first
	for(int i=0; i<templ->auxs_size; i++)
	{
		if(!templ->auxs[i]) continue;
		YAAMP_COIND *coind_aux = templ->auxs[i]->coind;

		unsigned char target_aux[1024];
		binlify(target_aux, coind_aux->aux.target);

		uint64_t coin_target_aux = get_hash_difficulty(target_aux);
		if(hash_int <= coin_target_aux)
		{
			memset(block_hex, 0, block_size);

			strcat(block_hex, submitvalues->coinbase);		// parent coinbase
			strcat(block_hex, submitvalues->hash_be);		// parent hash

			////////////////////////////////////////////////// parent merkle steps

			sprintf(block_hex+strlen(block_hex), "%02x", (unsigned char)templ->txsteps.size());

			vector<string>::const_iterator i;
			for(i = templ->txsteps.begin(); i != templ->txsteps.end(); ++i)
				sprintf(block_hex + strlen(block_hex), "%s", (*i).c_str());

			strcat(block_hex, "00000000");

			////////////////////////////////////////////////// auxs merkle steps

			vector<string> lresult = coind_aux_merkle_branch(templ->auxs, templ->auxs_size, coind_aux->aux.index);
			sprintf(block_hex+strlen(block_hex), "%02x", (unsigned char)lresult.size());

			for(i = lresult.begin(); i != lresult.end(); ++i)
				sprintf(block_hex+strlen(block_hex), "%s", (*i).c_str());

			sprintf(block_hex+strlen(block_hex), "%02x000000", (unsigned char)coind_aux->aux.index);

			////////////////////////////////////////////////// parent header

			strcat(block_hex, submitvalues->header_be);

			bool b = coind_submitgetauxblock(coind_aux, coind_aux->aux.hash, block_hex);
			if(b)
			{
				debuglog("*** ACCEPTED %s %d\n", coind_aux->name, coind_aux->height+1);

				block_add(client->userid, coind_aux->id, coind_aux->height, target_to_diff(coin_target_aux),
					target_to_diff(hash_int), coind_aux->aux.hash, "");
			}

			else
				debuglog("%s %d rejected\n", coind_aux->name, coind_aux->height+1);
		}
	}

	if(hash_int <= coin_target)
	{
		memset(block_hex, 0, block_size);
		sprintf(block_hex, "%s%02x%s", submitvalues->header_be, (unsigned char)templ->txcount, submitvalues->coinbase);

		vector<string>::const_iterator i;
		for(i = templ->txdata.begin(); i != templ->txdata.end(); ++i)
			sprintf(block_hex+strlen(block_hex), "%s", (*i).c_str());

		if(coind->txmessage)
			strcat(block_hex, "00");

		bool b = coind_submit(coind, block_hex);
		if(b)
		{
			debuglog("*** ACCEPTED %s %d\n", coind->name, templ->height);
			job->block_found = true;

			char doublehash2[128];
			memset(doublehash2, 0, 128);

			sha256_double_hash_hex((char *)submitvalues->header_bin, doublehash2, strlen(submitvalues->header_be)/2);

			char hash1[1024];
			memset(hash1, 0, 1024);

			string_be(doublehash2, hash1);

			block_add(client->userid, coind->id, templ->height, target_to_diff(coin_target), target_to_diff(hash_int),
				hash1, submitvalues->hash_be);

//			if(!strcmp(coind->symbol, "HAL"))
//			{
//				debuglog("--------------------------------------------------------------\n");
//				debuglog("hash1 %s\n", hash1);
//				debuglog("hash2 %s\n", submitvalues->hash_be);
//			}
		}

		else
			debuglog("%s %d rejected\n", coind->name, templ->height);
	}

	free(block_hex);
}

bool dump_submit_debug(const char *title, YAAMP_CLIENT *client, YAAMP_JOB *job, char *extranonce2, char *ntime, char *nonce)
{
	debuglog("ERROR %s, %s subs %d, job %x, %s, id %x, %d, %s, %s %s\n",
		title, client->sock->ip, client->extranonce_subscribe, job? job->id: 0, client->extranonce1,
		client->extranonce1_id, client->extranonce2size, extranonce2, ntime, nonce);
}

void client_submit_error(YAAMP_CLIENT *client, YAAMP_JOB *job, int id, const char *message, char *extranonce2, char *ntime, char *nonce)
{
//	if(job->templ->created+2 > time(NULL))
	if(job && job->deleted)
		client_send_result(client, "true");

	else
	{
		client_send_error(client, id, message);
		share_add(client, job, false, extranonce2, ntime, nonce, id);

		client->submit_bad++;
//		dump_submit_debug(message, client, job, extranonce2, ntime, nonce);
	}

	object_unlock(job);
}

bool client_submit(YAAMP_CLIENT *client, json_value *json_params)
{
	// submit(worker_name, jobid, extranonce2, ntime, nonce):
	if(json_params->u.array.length<5)
	{
		debuglog("%s - %s bad message\n", client->username, client->sock->ip);
		client->submit_bad++;
		return false;
	}

//	char name[1024];
	char extranonce2[32];
	char ntime[32];
	char nonce[32];

	memset(extranonce2, 0, 32);
	memset(ntime, 0, 32);
	memset(nonce, 0, 32);

	int jobid = htoi(json_params->u.array.values[1]->u.string.ptr);
	strncpy(extranonce2, json_params->u.array.values[2]->u.string.ptr, 31);
	strncpy(ntime, json_params->u.array.values[3]->u.string.ptr, 31);
	strncpy(nonce, json_params->u.array.values[4]->u.string.ptr, 31);

//	debuglog("submit %s %d, %s, %s, %s\n", client->sock->ip, jobid, extranonce2, ntime, nonce);

	string_lower(extranonce2);
	string_lower(ntime);
	string_lower(nonce);

	YAAMP_JOB *job = (YAAMP_JOB *)object_find(&g_list_job, jobid, true);
	if(!job)
	{
		client_submit_error(client, NULL, 21, "Invalid job id", extranonce2, ntime, nonce);
		return true;
	}

	if(job->deleted)
	{
		client_send_result(client, "true");
		object_unlock(job);

		return true;
	}

	YAAMP_JOB_TEMPLATE *templ = job->templ;
//	dump_submit_debug(client, job, extranonce2, ntime, nonce);

	if(strlen(nonce) != YAAMP_NONCE_SIZE*2)
	{
		client_submit_error(client, job, 20, "Invalid nonce size", extranonce2, ntime, nonce);
		return true;
	}

//	if(strcmp(ntime, templ->ntime))
//	{
//		client_submit_error(client, job, 23, "Invalid time rolling", extranonce2, ntime, nonce);
//		return true;
//	}

	YAAMP_SHARE *share = share_find(job->id, extranonce2, ntime, nonce, client->extranonce1);
	if(share)
	{
		client_submit_error(client, job, 22, "Duplicate share", extranonce2, ntime, nonce);
		return true;
	}

	if(strlen(extranonce2) != client->extranonce2size*2)
	{
		client_submit_error(client, job, 24, "Invalid extranonce2 size", extranonce2, ntime, nonce);
		return true;
	}

	///////////////////////////////////////////////////////////////////////////////////////////

	YAAMP_JOB_VALUES submitvalues;
	memset(&submitvalues, 0, sizeof(submitvalues));

	build_submit_values(&submitvalues, templ, client->extranonce1, extranonce2, ntime, nonce);
	if(submitvalues.hash_bin[30] || submitvalues.hash_bin[31])
	{
		client_submit_error(client, job, 25, "Invalid share", extranonce2, ntime, nonce);
		return true;
	}

	uint64_t hash_int = get_hash_difficulty(submitvalues.hash_bin);
	uint64_t user_target = diff_to_target(client->difficulty_actual);
	uint64_t coin_target = decode_compact(templ->nbits);

//	debuglog("%016llx actual\n", hash_int);
//	debuglog("%016llx target\n", user_target);
//	debuglog("%016llx coin\n", coin_target);

	if(hash_int > user_target && hash_int > coin_target)
	{
		client_submit_error(client, job, 26, "Low difficulty share", extranonce2, ntime, nonce);
		return true;
	}

	if(job->coind)
		client_do_submit(client, job, &submitvalues, extranonce2, ntime, nonce);
	else
		remote_submit(client, job, &submitvalues, extranonce2, ntime, nonce);

	client_send_result(client, "true");
	client_record_difficulty(client);
	client->submit_bad = 0;

	share_add(client, job, true, extranonce2, ntime, nonce, 0);
	object_unlock(job);

	return true;
}







