
#define YAAMP_SOCKET_BUFSIZE	(2*1024)

struct YAAMP_SOCKET
{
	char ip[1024];
	int port;

//	pthread_mutex_t mutex;
	int sock;

	int buflen;
	char buffer[YAAMP_SOCKET_BUFSIZE];

	int last_read;
	int total_read;
};

bool socket_connected(YAAMP_SOCKET *s);

YAAMP_SOCKET *socket_initialize(int sock);
void socket_close(YAAMP_SOCKET *s);

json_value *socket_nextjson(YAAMP_SOCKET *s, YAAMP_CLIENT *client=NULL);
int socket_send(YAAMP_SOCKET *s, const char *format, ...);

int socket_send_raw(YAAMP_SOCKET *s, const char *buffer, int size);

