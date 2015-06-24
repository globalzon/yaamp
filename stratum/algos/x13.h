#ifndef X13_H
#define X13_H

#ifdef __cplusplus
extern "C" {
#endif

#include <stdint.h>

void x13_hash(const char* input, char* output, uint32_t len);

#ifdef __cplusplus
}
#endif

#endif
