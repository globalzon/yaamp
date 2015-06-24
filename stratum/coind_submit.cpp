
#include "stratum.h"

bool coind_submitblock(YAAMP_COIND *coind, const char *block)
{
	int paramlen = strlen(block);

	char *params = (char *)malloc(paramlen+1024);
	if(!params) return false;

	sprintf(params, "[\"%s\"]", block);
	json_value *json = rpc_call(&coind->rpc, "submitblock", params);

	free(params);
	if(!json) return false;

	json_value *json_error = json_get_object(json, "error");
	if(json_error && json_error->type != json_null)
	{
		const char *p = json_get_string(json_error, "message");
		if(p) stratumlog("ERROR %s %s\n", coind->name, p);

	//	job_reset();
		json_value_free(json);

		return false;
	}

	json_value *json_result = json_get_object(json, "result");

	bool b = json_result && json_result->type == json_null;
	json_value_free(json);

	return b;
}

bool coind_submitblocktemplate(YAAMP_COIND *coind, const char *block)
{
	int paramlen = strlen(block);

	char *params = (char *)malloc(paramlen+1024);
	if(!params) return false;

	sprintf(params, "[{\"mode\": \"submit\", \"data\": \"%s\"}]", block);
	json_value *json = rpc_call(&coind->rpc, "getblocktemplate", params);

	free(params);
	if(!json) return false;

	json_value *json_error = json_get_object(json, "error");
	if(json_error && json_error->type != json_null)
	{
		const char *p = json_get_string(json_error, "message");
		if(p) stratumlog("ERROR %s %s\n", coind->name, p);

	//	job_reset();
		json_value_free(json);

		return false;
	}

	json_value *json_result = json_get_object(json, "result");

	bool b = json_result && json_result->type == json_null;
	json_value_free(json);

	return b;
}

bool coind_submit(YAAMP_COIND *coind, const char *block)
{
	bool b;

	if(coind->hassubmitblock)
		b = coind_submitblock(coind, block);
	else
		b = coind_submitblocktemplate(coind, block);

	return b;
}

bool coind_submitgetauxblock(YAAMP_COIND *coind, const char *hash, const char *block)
{
	int paramlen = strlen(block);

	char *params = (char *)malloc(paramlen+1024);
	if(!params) return false;

	sprintf(params, "[\"%s\",\"%s\"]", hash, block);
	json_value *json = rpc_call(&coind->rpc, "getauxblock", params);

	free(params);
	if(!json) return false;

	json_value *json_error = json_get_object(json, "error");
	if(json_error && json_error->type != json_null)
	{
		const char *p = json_get_string(json_error, "message");
		if(p) stratumlog("ERROR %s %s\n", coind->name, p);

	//	job_reset();
		json_value_free(json);

		return false;
	}

	json_value *json_result = json_get_object(json, "result");
	bool b = json_result && json_result->type == json_boolean && json_result->u.boolean;

	json_value_free(json);
	return b;
}

