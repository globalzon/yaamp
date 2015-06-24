#ifndef JHA_H__
#define JHA_H__

#ifdef __cplusplus
extern "C" {
#endif

#include <stdint.h>

void jha_hash(const char* input, char* output, uint32_t len);

#ifdef __cplusplus
}
#endif

#endif
