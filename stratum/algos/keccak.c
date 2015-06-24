
#include <stdint.h>
#include <stdlib.h>
#include <string.h>
#include <stdio.h>

#include "../sha3/sph_types.h"
#include "../sha3/sph_keccak.h"

void keccak_hash(const char *input, char *output, uint32_t len)
{
	sph_keccak256_context ctx_keccak;
	sph_keccak256_init(&ctx_keccak);

	sph_keccak256(&ctx_keccak, input, len);
	sph_keccak256_close(&ctx_keccak, output);
}

//void keccak_hash2(const char *input, char *output, uint32_t len)
//{
//	uint32_t hashA[16];
//
//	sph_keccak512_context ctx_keccak;
//	sph_keccak512_init(&ctx_keccak);
//
//	sph_keccak512(&ctx_keccak, input, len);
//	sph_keccak512_close(&ctx_keccak, hashA);
//
//	memcpy(output, hashA, 32);
//}


