#include "x15.h"
#include <stdlib.h>
#include <stdint.h>
#include <string.h>
#include <stdio.h>

#include "../sha3/sph_blake.h"
#include "../sha3/sph_bmw.h"
#include "../sha3/sph_groestl.h"
#include "../sha3/sph_jh.h"
#include "../sha3/sph_keccak.h"
#include "../sha3/sph_skein.h"
#include "../sha3/sph_luffa.h"
#include "../sha3/sph_cubehash.h"
#include "../sha3/sph_shavite.h"
#include "../sha3/sph_simd.h"
#include "../sha3/sph_echo.h"
#include "../sha3/sph_hamsi.h"
#include "../sha3/sph_fugue.h"
#include "../sha3/sph_shabal.h"
#include "../sha3/sph_whirlpool.h"

void whirlpoolx_hash(const char* input, char* output, uint32_t len)
{
	unsigned char hash[64];
	memset(hash, 0, sizeof(hash));

	sph_whirlpool_context ctx_whirlpool;

	sph_whirlpool_init(&ctx_whirlpool);
	sph_whirlpool(&ctx_whirlpool, input, len);
	sph_whirlpool_close(&ctx_whirlpool, hash);

    unsigned char hash_xored[sizeof(hash) / 2];

    uint32_t i;
	for (i = 0; i < (sizeof(hash) / 2); i++)
	{
        hash_xored[i] = hash[i] ^ hash[i + ((sizeof(hash) / 2) / 2)];
	}

	memcpy(output, hash_xored, 32);
}

