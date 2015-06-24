
#define YAAMP_REMOTE_CLOSED			0
#define YAAMP_REMOTE_SUBSCRIBE		1
#define YAAMP_REMOTE_AUTHORIZE		2
#define YAAMP_REMOTE_EXTRANONCE		3
#define YAAMP_REMOTE_READY			4
#define YAAMP_REMOTE_RESET			5
#define YAAMP_REMOTE_TERMINATE		6

class YAAMP_SUBMIT;

class YAAMP_RENTER: public YAAMP_OBJECT
{
public:
	double balance;
	int updated;
};

class YAAMP_REMOTE: public YAAMP_OBJECT
{
public:
	bool touch;
//	bool allocated;

	bool kill;
//	bool reset_balance;

	int status;
	int updated;

	YAAMP_RENTER *renter;

	pthread_t thread;
	YAAMP_SOCKET *sock;

	char jobid[32];
	char nonce1[32];
	int nonce2size;
	char nonce1_next[32];
	int nonce2size_next;

	double difficulty_actual;
	double difficulty_next;
	double difficulty_written;

	double price;
	double speed;
	double speed_avg;

//	char session_id[1024];

	char host[1024];
	int port;

	char username[1024];
	char password[1024];

	YAAMP_JOB *job;
	YAAMP_SUBMIT *submit_last;
};

inline void remote_delete(YAAMP_OBJECT *object)
{
	YAAMP_REMOTE *remote = (YAAMP_REMOTE *)object;

	object_delete(remote->job);
	socket_close(remote->sock);

	pthread_detach(remote->thread);
	delete remote;
}

bool remote_can_mine(YAAMP_REMOTE *remote);
void remote_sort();

bool remote_connected(YAAMP_REMOTE *remote);
void remote_close(YAAMP_REMOTE *remote);
void *remote_thread(void *p);

void remote_create_job(YAAMP_REMOTE *remote, json_value *json_params);
void remote_submit(YAAMP_CLIENT *client, YAAMP_JOB *job, YAAMP_JOB_VALUES *submitvalues, char *extranonce2, char *ntime, char *nonce);















