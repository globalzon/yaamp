
class YAAMP_WORKER: public YAAMP_OBJECT
{
public:
	int userid;
	int workerid;
	int coinid;
	int remoteid;

	bool valid;
	bool extranonce1;

	int error_number;
	double difficulty;
};

inline void worker_delete(YAAMP_OBJECT *object)
{
	YAAMP_WORKER *worker = (YAAMP_WORKER *)object;
	delete worker;
}

//////////////////////////////////////////////////////////////////////////////////////////////////

class YAAMP_SHARE: public YAAMP_OBJECT
{
public:
	int jobid;
	char extranonce2[32];
	char ntime[32];
	char nonce[32];
	char nonce1[32];
};

inline void share_delete(YAAMP_OBJECT *object)
{
	YAAMP_SHARE *share = (YAAMP_SHARE *)object;
	delete share;
}

//YAAMP_WORKER *share_find_worker(int userid, int workerid, int coinid, bool valid);
//void share_add_worker(int userid, int workerid, int coinid, bool valid, double difficulty);

///////////

YAAMP_SHARE *share_find(int jobid, char *extranonce2, char *ntime, char *nonce, char *nonce1);
void share_add(YAAMP_CLIENT *client, YAAMP_JOB *job, bool valid, char *extranonce2, char *ntime, char *nonce, int error_number);

void share_write(YAAMP_DB *db);
void share_prune(YAAMP_DB *db);

////////////////////////////////////////////////////////////////////////////////

class YAAMP_BLOCK: public YAAMP_OBJECT
{
public:
	int created;
	bool confirmed;

	int userid;
	int coinid;
	int height;

	double difficulty;
	double difficulty_user;

	char hash[1024];
	char hash1[1024];
	char hash2[1024];
};

inline void block_delete(YAAMP_OBJECT *object)
{
	YAAMP_BLOCK *block = (YAAMP_BLOCK *)object;
	delete block;
}

////////////////////////////////////////////////////////////////////////////////////

class YAAMP_SUBMIT: public YAAMP_OBJECT
{
public:
	int created;
	bool valid;

	int remoteid;
	double difficulty;
};

inline void submit_delete(YAAMP_OBJECT *object)
{
	YAAMP_SUBMIT *submit = (YAAMP_SUBMIT *)object;
	delete submit;
}

void block_prune(YAAMP_DB *db);

void block_add(int userid, int coinid, int height, double difficulty, double difficulty_user, const char *hash1, const char *hash2);
void block_confirm(int coinid, const char *hash);

YAAMP_SUBMIT *submit_add(int remoteid, double difficulty);
void submit_prune(YAAMP_DB *db);




