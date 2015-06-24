
CC=gcc

#CFLAGS=-c -g -I /usr/include/mysql
#LDFLAGS=-g

CFLAGS=-c -O2 -I /usr/include/mysql
LDFLAGS=-O2

SOURCES=Lyra2RE.c Lyra2.c Sponge.c blake.c scrypt.c x11.c x13.c sha256.c keccak.c \
	x14.c x15.c nist5.c fresh.c quark.c neoscrypt.c scryptn.c qubit.c skein.c groestl.c

OBJECTS=$(SOURCES:.c=.o)
OUTPUT=libalgos.a

all: $(SOURCES) $(OUTPUT)

$(OUTPUT): $(OBJECTS) 
	ar rc $@ $(OBJECTS)
	
.cpp.o:
	$(CC) $(CFLAGS) $<
	
.c.o:
	$(CC) $(CFLAGS) $<

#	$(CC) $(CFLAGS) -std=gnu99 -Wno-pointer-sign -Wno-pointer-to-int-cast -funroll-loops -fvariable-expansion-in-unroller -fmerge-all-constants -fbranch-target-load-optimize2 -fsched2-use-superblocks -falign-loops=16 -falign-functions=16 -falign-jumps=16 -falign-labels=16 -Ofast -flto -fuse-linker-plugin -ftree-loop-if-convert-stores -DUSE_ASM -pg $<
 
clean:
	rm *.o

	
