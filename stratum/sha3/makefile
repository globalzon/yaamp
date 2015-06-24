
CC=gcc

#CFLAGS=-c -g -I /usr/include/mysql
#LDFLAGS=-g

CFLAGS=-c -O2 -I /usr/include/mysql
LDFLAGS=-O2

SOURCES=sph_jh.c sph_blake.c sph_bmw.c sph_groestl.c sph_skein.c sph_keccak.c sph_luffa.c sph_cubehash.c sph_shavite.c \
	sph_simd.c sph_echo.c sph_fugue.c sph_hamsi.c sph_shabal.c sph_whirlpool.c 

OBJECTS=$(SOURCES:.c=.o)
OUTPUT=libhash.a

all: $(SOURCES) $(OUTPUT)

$(OUTPUT): $(OBJECTS)
	ar rc $@ $(OBJECTS)

.cpp.o:
	$(CC) $(CFLAGS) $<
 
.c.o:
	$(CC) $(CFLAGS) $<

clean:
	rm *.o






