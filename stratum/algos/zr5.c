#include "x15.h"
#include <stdlib.h>
#include <stdint.h>
#include <string.h>
#include <stdio.h>

#include "../sha3/sph_blake.h"
#include "../sha3/sph_groestl.h"
#include "../sha3/sph_jh.h"
#include "../sha3/sph_keccak.h"
#include "../sha3/sph_skein.h"

#define ZR_BLAKE   0
#define ZR_GROESTL 1
#define ZR_JH      2
#define ZR_SKEIN   3

#define POK_BOOL_MASK 0x00008000
#define POK_DATA_MASK 0xFFFF0000

#define ARRAY_SIZE(arr) (sizeof(arr) / sizeof((arr)[0]))

static const int permut[][4] = {
	{0, 1, 2, 3},
	{0, 1, 3, 2},
	{0, 2, 1, 3},
	{0, 2, 3, 1},
	{0, 3, 1, 2},
	{0, 3, 2, 1},
	{1, 0, 2, 3},
	{1, 0, 3, 2},
	{1, 2, 0, 3},
	{1, 2, 3, 0},
	{1, 3, 0, 2},
	{1, 3, 2, 0},
	{2, 0, 1, 3},
	{2, 0, 3, 1},
	{2, 1, 0, 3},
	{2, 1, 3, 0},
	{2, 3, 0, 1},
	{2, 3, 1, 0},
	{3, 0, 1, 2},
	{3, 0, 2, 1},
	{3, 1, 0, 2},
	{3, 1, 2, 0},
	{3, 2, 0, 1},
	{3, 2, 1, 0}
};

void zr5_hash(const char* input, char* output, uint32_t len)
{
	sph_keccak512_context ctx_keccak;
	sph_blake512_context ctx_blake;
	sph_groestl512_context ctx_groestl;
	sph_jh512_context ctx_jh;
	sph_skein512_context ctx_skein;

	uint32_t hash[5][16];
	char *ph = (char *)hash;

	sph_keccak512_init(&ctx_keccak);
	sph_keccak512(&ctx_keccak, (const void*) input, len);
	sph_keccak512_close(&ctx_keccak, (void*) &hash[0][0]);

	unsigned int norder = hash[0][0] % ARRAY_SIZE(permut); /* % 24 */
	int i;

	for(i=0; i<len; i++) printf("%02x", (unsigned char)input[i]); printf("\n");
	for(i=0; i<32; i++) printf("%02x", (unsigned char)ph[i]); printf("\n");

	for(i = 0; i < 4; i++)
	{
		void* phash = (void*) &(hash[i][0]);
		void* pdest = (void*) &(hash[i+1][0]);

		printf("permut %d\n", permut[norder][i]);
		switch (permut[norder][i]) {
		case ZR_BLAKE:
			sph_blake512_init(&ctx_blake);
			sph_blake512(&ctx_blake, (const void*) phash, 64);
			sph_blake512_close(&ctx_blake, pdest);
			break;
		case ZR_GROESTL:
			sph_groestl512_init(&ctx_groestl);
			sph_groestl512(&ctx_groestl, (const void*) phash, 64);
			sph_groestl512_close(&ctx_groestl, pdest);
			break;
		case ZR_JH:
			sph_jh512_init(&ctx_jh);
			sph_jh512(&ctx_jh, (const void*) phash, 64);
			sph_jh512_close(&ctx_jh, pdest);
			break;
		case ZR_SKEIN:
			sph_skein512_init(&ctx_skein);
			sph_skein512(&ctx_skein, (const void*) phash, 64);
			sph_skein512_close(&ctx_skein, pdest);
			break;
		default:
			break;
		}
	}
	memcpy(output, &hash[4], 32);
}

