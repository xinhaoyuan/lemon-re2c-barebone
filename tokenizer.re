#include <string>
#include <sstream>
#include <iostream>
#include <stdlib.h>
#include <string.h>

#include "driver.hpp"
#include "parser.h"

int tokenizer_scan(TokenizerState &ts) {
#define YYLIMIT  ts.limit
#define YYCURSOR ts.cursor
#define YYMARKER ts.marker
#define YYFILL(n) err = tokenizer_fill(ts, n)
    int fill_n;
    int err = 0;
    ts.start = ts.cursor;
    ts.token_type = -1;
    while (ts.token_type == -1 && err == 0) {
        /*!re2c
          /* skip */
          [\t \n]                    { ts.start = YYCURSOR; goto finish; }
          [/][/].*                   { ts.start = YYCURSOR; goto finish; }
          /* match */
          [\000]                     { ts.token_type = T_EOF; goto finish; }
          .                          { err = 1; goto finish; }
        */

      finish:
        continue;
    }
    
    return err;
};
