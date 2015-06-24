
CC=gcc

CFLAGS=-c -g -I /usr/include/mysql
LDFLAGS=-g

#CFLAGS=-c -O2 -I /usr/include/mysql
#LDFLAGS=-O2

LDLIBS=iniparser/libiniparser.a algos/libalgos.a sha3/libhash.a -lmysqlclient -lpthread -lm -lstdc++

SOURCES=stratum.cpp db.cpp coind.cpp coind_aux.cpp coind_template.cpp coind_submit.cpp util.cpp list.cpp \
	rpc.cpp job.cpp job_send.cpp job_core.cpp merkle.cpp share.cpp socket.cpp coinbase.cpp \
	client.cpp client_submit.cpp client_core.cpp client_difficulty.cpp remote.cpp remote_template.cpp \
	user.cpp object.cpp json.cpp base58.cpp 

OBJECTS=$(SOURCES:.cpp=.o)
OUTPUT=stratum

CODEDIR1=algos
CODEDIR2=sha3

.PHONY: projectcode1 projectcode2

all: projectcode1 projectcode2 $(SOURCES) $(OUTPUT)

projectcode1:
	$(MAKE) -C $(CODEDIR1)

projectcode2:
	$(MAKE) -C $(CODEDIR2)

$(SOURCES): stratum.h util.h

$(OUTPUT): $(OBJECTS) 
	$(CC) $(LDFLAGS) $(OBJECTS) $(LDLIBS) -o $@
	
.cpp.o:
	$(CC) $(CFLAGS) $<
	
.c.o:
	$(CC) $(CFLAGS) $<

clean:
	rm *.o
#	rm algos/*.o
#	rm sha3/*.o

install: all
	cp stratum release
	strip release/stratum

	
