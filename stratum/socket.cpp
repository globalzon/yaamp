
#include "stratum.h"

//#define SOCKET_DEBUGLOG_

bool socket_connected(YAAMP_SOCKET *s)
{
	return s->sock > 0;
}

YAAMP_SOCKET *socket_initialize(int sock)
{
	YAAMP_SOCKET *s = new YAAMP_SOCKET;
	memset(s, 0, sizeof(YAAMP_SOCKET));

	s->buflen = 0;
	s->sock = sock;

//	yaamp_create_mutex(&s->mutex);
//	pthread_mutex_lock(&s->mutex);

	struct sockaddr_in name;
	socklen_t len = sizeof(name);
	memset(&name, 0, len);

	int res = getpeername(s->sock, (struct sockaddr *)&name, &len);
	inet_ntop(AF_INET, &name.sin_addr, s->ip, 1024);

	res = getsockname(s->sock, (struct sockaddr *)&name, &len);
	s->port = ntohs(name.sin_port);

	return s;
}

void socket_close(YAAMP_SOCKET *s)
{
#ifdef SOCKET_DEBUGLOG_
	debuglog("socket_close\n");
#endif

	if(!s) return;
	if(s->sock) close(s->sock);

//	pthread_mutex_unlock(&s->mutex);
//	pthread_mutex_destroy(&s->mutex);

	s->sock = 0;
	delete s;
}

json_value *socket_nextjson(YAAMP_SOCKET *s, YAAMP_CLIENT *client)
{
	while(!strchr(s->buffer, '}') && s->buflen<YAAMP_SOCKET_BUFSIZE-1)
	{
	//	pthread_mutex_unlock(&s->mutex);

		int len = recv(s->sock, s->buffer+s->buflen, YAAMP_SOCKET_BUFSIZE-s->buflen-1, 0);
		if(len <= 0) return NULL;

		s->last_read = time(NULL);
		s->total_read += len;

		s->buflen += len;
		s->buffer[s->buflen] = 0;

//		if(client && client->logtraffic)
//			stratumlog("recv: %s\n", s->buffer);

	//	pthread_mutex_lock(&s->mutex);
	}

	char *p = strchr(s->buffer, '}');
	if(!p)
	{
		if(client)
			clientlog(client, "bad json");

		debuglog("%s\n", s->buffer);
		return NULL;
	}

	p++;

	char saved = *p;
	*p = 0;

	if(client && client->logtraffic)
		stratumlog("%s, %s, %s, %s, recv: %s\n", client->sock->ip, client->username, client->password, g_current_algo->name, s->buffer);

	int bytes = strlen(s->buffer);

	json_value *json = json_parse(s->buffer, bytes);
	if(!json)
	{
		if(client)
			clientlog(client, "bad json parse");

		debuglog("%s\n", s->buffer);
		return NULL;
	}

	*p = saved;
	while(*p && *p != '{')
		p++;

	if(*p == '{')
	{
		memmove(s->buffer, p, s->buflen - (p - s->buffer));

		s->buflen = s->buflen - (p - s->buffer);
		s->buffer[s->buflen] = 0;

//		if(client && client->logtraffic)
//			stratumlog("still: %s\n", s->buffer);
	}
	else
	{
		memset(s->buffer, 0, YAAMP_SOCKET_BUFSIZE);
		s->buflen = 0;
	}

	return json;
}

int socket_send_raw(YAAMP_SOCKET *s, const char *buffer, int size)
{
#ifdef SOCKET_DEBUGLOG_
	debuglog("socket send: %s", buffer);
#endif

	int res = send(s->sock, buffer, size, MSG_NOSIGNAL);
	return res;
}

int socket_send(YAAMP_SOCKET *s, const char *format, ...)
{
	char buffer[YAAMP_SMALLBUFSIZE];
	va_list args;

	va_start(args, format);
	vsprintf(buffer, format, args);
	va_end(args);

//	json_value *json = json_parse(buffer, strlen(buffer));
//	if(!json)
//		debuglog("sending bad json message: %s\n", buffer);
//	else
//		json_value_free(json);

//	pthread_mutex_lock(&s->mutex);
	int res = socket_send_raw(s, buffer, strlen(buffer));

//	pthread_mutex_unlock(&s->mutex);
	return res;
}




