
#include "stratum.h"

void coind_getauxblock(YAAMP_COIND *coind)
{
	if(!coind->isaux) return;

	json_value *json = rpc_call(&coind->rpc, "getauxblock", "[]");
	if(!json)
	{
		coind_error(coind, "coind_getauxblock");
		return;
	}

	json_value *json_result = json_get_object(json, "result");
	if(!json_result)
	{
		coind_error(coind, "coind_getauxblock");
		return;
	}

//	coind->aux.height = coind->height+1;
	coind->aux.chainid = json_get_int(json_result, "chainid");

	const char *p = json_get_string(json_result, "target");
	if(p) strcpy(coind->aux.target, p);

	p = json_get_string(json_result, "hash");
	if(p) strcpy(coind->aux.hash, p);

//	if(strcmp(coind->symbol, "UNO") == 0)
//	{
//		string_be1(coind->aux.target);
//		string_be1(coind->aux.hash);
//	}

	json_value_free(json);
}

YAAMP_JOB_TEMPLATE *coind_create_template_memorypool(YAAMP_COIND *coind)
{
	json_value *json = rpc_call(&coind->rpc, "getmemorypool");
	if(!json || json->type == json_null)
	{
		coind_error(coind, "getmemorypool");
		return NULL;
	}

	json_value *json_result = json_get_object(json, "result");
	if(!json_result || json_result->type == json_null)
	{
		coind_error(coind, "getmemorypool");
		json_value_free(json);

		return NULL;
	}

	YAAMP_JOB_TEMPLATE *templ = new YAAMP_JOB_TEMPLATE;
	memset(templ, 0, sizeof(YAAMP_JOB_TEMPLATE));

	templ->created = time(NULL);
	templ->value = json_get_int(json_result, "coinbasevalue");
//	templ->height = json_get_int(json_result, "height");
	sprintf(templ->version, "%08x", (unsigned int)json_get_int(json_result, "version"));
	sprintf(templ->ntime, "%08x", (unsigned int)json_get_int(json_result, "time"));
	strcpy(templ->nbits, json_get_string(json_result, "bits"));
	strcpy(templ->prevhash_hex, json_get_string(json_result, "previousblockhash"));

	json_value_free(json);

	json = rpc_call(&coind->rpc, "getinfo", "[]");
	if(!json || json->type == json_null)
	{
		coind_error(coind, "coind_getinfo");
		return NULL;
	}

	json_result = json_get_object(json, "result");
	if(!json_result || json_result->type == json_null)
	{
		coind_error(coind, "coind_getinfo");
		json_value_free(json);

		return NULL;
	}

	templ->height = json_get_int(json_result, "blocks")+1;
	json_value_free(json);

	if(coind->isaux)
		coind_getauxblock(coind);

	coind->usememorypool = true;
	return templ;
}

////////////////////////////////////////////////////////////////////////////////////////////////////////////

YAAMP_JOB_TEMPLATE *coind_create_template(YAAMP_COIND *coind)
{
	if(coind->usememorypool)
		return coind_create_template_memorypool(coind);

	char params[4*1024] = "[{}]";
	if(!strcmp(coind->symbol, "PPC")) strcpy(params, "[]");

	json_value *json = rpc_call(&coind->rpc, "getblocktemplate", params);
	if(!json || json->type == json_null)
	{
		coind_error(coind, "getblocktemplate");
		return NULL;
	}

	json_value *json_result = json_get_object(json, "result");
	if(!json_result || json_result->type == json_null)
	{
		coind_error(coind, "getblocktemplate");
		json_value_free(json);

		return NULL;
	}

	json_value *json_tx = json_get_array(json_result, "transactions");
	if(!json_tx)
	{
		coind_error(coind, "getblocktemplate");
		json_value_free(json);

		return NULL;
	}

	json_value *json_coinbaseaux = json_get_object(json_result, "coinbaseaux");
	if(!json_coinbaseaux)
	{
		coind_error(coind, "getblocktemplate");
		json_value_free(json);

		return NULL;
	}

	YAAMP_JOB_TEMPLATE *templ = new YAAMP_JOB_TEMPLATE;
	memset(templ, 0, sizeof(YAAMP_JOB_TEMPLATE));

	templ->created = time(NULL);
	templ->value = json_get_int(json_result, "coinbasevalue");
	templ->height = json_get_int(json_result, "height");
	sprintf(templ->version, "%08x", (unsigned int)json_get_int(json_result, "version"));
	sprintf(templ->ntime, "%08x", (unsigned int)json_get_int(json_result, "curtime"));
	strcpy(templ->nbits, json_get_string(json_result, "bits"));
	strcpy(templ->prevhash_hex, json_get_string(json_result, "previousblockhash"));
	strcpy(templ->flags, json_get_string(json_coinbaseaux, "flags"));

//	debuglog("%s ntime %s\n", coind->symbol, templ->ntime);
//	uint64_t target = decode_compact(json_get_string(json_result, "bits"));
//	coind->difficulty = target_to_diff(target);

//	string_lower(templ->ntime);
//	string_lower(templ->nbits);

//	char target[1024];
//	strcpy(target, json_get_string(json_result, "target"));
//	uint64_t coin_target = decode_compact(templ->nbits);
//	debuglog("%s\n", templ->nbits);
//	debuglog("%s\n", target);
//	debuglog("0000%016llx\n", coin_target);

	if(coind->isaux)
	{
		json_value_free(json);

		coind_getauxblock(coind);
		return templ;
	}

	//////////////////////////////////////////////////////////////////////////////////////////

	vector<string> txhashes;
	txhashes.push_back("");

	for(int i = 0; i < json_tx->u.array.length; i++)
	{
		const char *p = json_get_string(json_tx->u.array.values[i], "hash");

		char hash_be[1024];
		memset(hash_be, 0, 1024);
		string_be(p, hash_be);

		txhashes.push_back(hash_be);

		const char *d = json_get_string(json_tx->u.array.values[i], "data");
		templ->txdata.push_back(d);
	}

	templ->txmerkles[0] = 0;
	templ->txcount = txhashes.size();
	templ->txsteps = merkle_steps(txhashes);

	vector<string>::const_iterator i;
	for(i = templ->txsteps.begin(); i != templ->txsteps.end(); ++i)
		sprintf(templ->txmerkles + strlen(templ->txmerkles), "\"%s\",", (*i).c_str());

	if(templ->txmerkles[0])
		templ->txmerkles[strlen(templ->txmerkles)-1] = 0;

//	debuglog("merkle transactions %d [%s]\n", templ->txcount, templ->txmerkles);
	ser_string_be2(templ->prevhash_hex, templ->prevhash_be, 8);

	if(!coind->pos)
		coind_aux_build_auxs(templ);

	coinbase_create(coind, templ, json_result);
	json_value_free(json);

	return templ;
}

////////////////////////////////////////////////////////////////////////////////////////////////////////////

void coind_create_job(YAAMP_COIND *coind, bool force)
{
//	debuglog("create job %s\n", coind->symbol);

	bool b = rpc_connected(&coind->rpc);
	if(!b) return;

	CommonLock(&coind->mutex);

	YAAMP_JOB_TEMPLATE *templ = coind_create_template(coind);
	if(!templ)
	{
		CommonUnlock(&coind->mutex);
		return;
	}

	YAAMP_JOB *job_last = coind->job;

	if(	!force && job_last && job_last->templ && job_last->templ->created + 45 > time(NULL) &&
		templ->height == job_last->templ->height &&
		templ->txcount == job_last->templ->txcount &&
		strcmp(templ->coinb2, job_last->templ->coinb2) == 0)
	{
//		debuglog("coind_create_job %s %d same template %x \n", coind->name, coind->height, coind->job->id);
		delete templ;

		CommonUnlock(&coind->mutex);
		return;
	}

	////////////////////////////////////////////////////////////////////////////////////////

	int height = coind->height;
	coind->height = templ->height-1;

	if(height > coind->height)
	{
		stratumlog("%s went from %d to %d\n", coind->name, height, coind->height);
	//	coind->auto_ready = false;
	}

	if(height < coind->height && !coind->newblock)
	{
		if(coind->auto_ready && coind->notreportingcounter++ > 5)
			stratumlog("%s %d not reporting\n", coind->name, coind->height);
	}

	uint64_t coin_target = decode_compact(templ->nbits);
	coind->difficulty = target_to_diff(coin_target);

	coind->newblock = false;

	////////////////////////////////////////////////////////////////////////////////////////

	object_delete(coind->job);

	coind->job = new YAAMP_JOB;
	memset(coind->job, 0, sizeof(YAAMP_JOB));

	sprintf(coind->job->name, "%s", coind->symbol);

	coind->job->id = job_get_jobid();
	coind->job->templ = templ;

	coind->job->profit = coind_profitability(coind);
	coind->job->maxspeed = coind_nethash(coind) *
		(g_current_algo->profit? min(1.0, coind_profitability(coind)/g_current_algo->profit): 1);

	coind->job->coind = coind;
	coind->job->remote = NULL;

	g_list_job.AddTail(coind->job);
	CommonUnlock(&coind->mutex);

//	debuglog("coind_create_job %s %d new job %x\n", coind->name, coind->height, coind->job->id);
}















