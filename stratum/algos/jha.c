#include "jha.h"

#include <stdlib.h>
#include <stdint.h>
#include <string.h>
#include <stdio.h>

#include "../sha3/sph_blake.h"
#include "../sha3/sph_groestl.h"
#include "../sha3/sph_jh.h"
#include "../sha3/sph_keccak.h"
#include "../sha3/sph_skein.h"


void jha_hash(const char* input, char* output, uint32_t len) {

     sph_blake512_context     ctx_blake;
     sph_groestl512_context   ctx_groestl;
     sph_jh512_context        ctx_jh;
     sph_keccak512_context    ctx_keccak;
     sph_skein512_context     ctx_skein;

     uint32_t hash[16];

     unsigned int round_mask = (
       (unsigned int)(((unsigned char *)input)[84]) <<  0 |
       (unsigned int)(((unsigned char *)input)[85]) <<  8 |
       (unsigned int)(((unsigned char *)input)[86]) << 16 |
       (unsigned int)(((unsigned char *)input)[87]) << 24 );

     //
     // JHA V7
     //
     if (round_mask == 7) {

        //
        // Input Hashing with SHA3 512, 88 bytes
        //
        sph_keccak512_init(&ctx_keccak);
        sph_keccak512 (&ctx_keccak, input, 88);
        sph_keccak512_close(&ctx_keccak, hash);

        //
        // Variable Rounds Loop
        //
        unsigned int rounds  = hash[0] & 7;
        unsigned int round;
        for (round = 0; round < rounds; round++) {
            switch (hash[0] & 3) {
              case 0:
                   sph_blake512_init(&ctx_blake);
                   sph_blake512 (&ctx_blake, hash, 64);
                   sph_blake512_close(&ctx_blake, hash);
                   break;
              case 1:
                   sph_groestl512_init(&ctx_groestl);
                   sph_groestl512 (&ctx_groestl, hash, 64);
                   sph_groestl512_close(&ctx_groestl, hash);
                   break;
              case 2:
                   sph_jh512_init(&ctx_jh);
                   sph_jh512 (&ctx_jh, hash, 64);
                   sph_jh512_close(&ctx_jh, hash);
                   break;
              case 3:
                   sph_skein512_init(&ctx_skein);
                   sph_skein512 (&ctx_skein, hash, 64);
                   sph_skein512_close(&ctx_skein, hash);
                   break;
            }
        }

        //
        // Return 256bit(32x8)
        //
   	    memcpy(output, hash, 32);

     }

     //
     // JHA V8
     //
     else if (round_mask == 8) {

        //
        // Input Hashing with SHA3 512, 80 bytes
        //
        sph_keccak512_init(&ctx_keccak);
        sph_keccak512 (&ctx_keccak, input, 80);
        sph_keccak512_close(&ctx_keccak, (&hash));

        //
        // Heavy & Light Pair Loop
        //
        unsigned int round;
        for (round = 0; round < 3; round++) {
            if (hash[0] & 0x01) {
               sph_groestl512_init(&ctx_groestl);
               sph_groestl512 (&ctx_groestl, (&hash), 64);
               sph_groestl512_close(&ctx_groestl, (&hash));
            }
            else {
               sph_skein512_init(&ctx_skein);
               sph_skein512 (&ctx_skein, (&hash), 64);
               sph_skein512_close(&ctx_skein, (&hash));
            }
            if (hash[0] & 0x01) {
               sph_blake512_init(&ctx_blake);
               sph_blake512 (&ctx_blake, (&hash), 64);
               sph_blake512_close(&ctx_blake, (&hash));
            }
            else {
               sph_jh512_init(&ctx_jh);
               sph_jh512 (&ctx_jh, (&hash), 64);
               sph_jh512_close(&ctx_jh, (&hash));
            }
        }

        //
        // Return 256bit(32x8)
        //
   	    memcpy(output, hash, 32);

     }

     //
     // Wrong Round Mask Data
     //
     else {

		memset(output, 0xFF, 32);

	 }

}
