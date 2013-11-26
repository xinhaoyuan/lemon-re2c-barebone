#include "driver.hpp"
#include "parser.h"

#include <string>
#include <sstream>
#include <vector>

#include <stdlib.h>
#include <string.h>
#include <assert.h>

// used by tokenizer

int tokenizer_init(TokenizerState &ts) {
    ts.buf = (YYCTYPE *)malloc(sizeof(YYCTYPE) * TOKENIZER_BUF_ALLOC);
    if (ts.buf == NULL) return -1;
    ts.buf_alloc = TOKENIZER_BUF_ALLOC;
    ts.buf_len = 0;
    ts.limit = ts.buf;
    ts.cursor = ts.buf;
}

void *tokenizer_destroy(TokenizerState &ts) {
    free(ts.buf);
    ts.buf_alloc = 0;
    ts.buf_len = 0;
    ts.buf = NULL;
    ts.cursor = NULL;
    ts.limit = NULL;
}

int tokenizer_fill(TokenizerState &ts, int n) {
    if (ts.buf_len + n > ts.buf_alloc) {
        int marker_offset = ts.marker - ts.buf;
        int cursor_offset = ts.cursor - ts.buf;
        int limit_offset  = ts.limit  - ts.buf;
        int start_offset  = ts.start  - ts.buf;

        int n_alloc = ts.buf_alloc;
        do { n_alloc <<= 1; } while (ts.buf_len + n > n_alloc);
        char *n_buf = (YYCTYPE *)realloc(ts.buf, sizeof(YYCTYPE) * n_alloc);

        if (n_buf == NULL) {
            return 1;
        }
        ts.buf_alloc = n_alloc;
        ts.buf = n_buf;

        ts.marker = ts.buf + marker_offset;
        ts.cursor = ts.buf + cursor_offset;
        ts.limit  = ts.buf + limit_offset;
        ts.start  = ts.buf + start_offset;
    }

    ts.is->read(ts.limit, n);
    int c = ts.is->gcount();
    if (c < n) {
        memset(ts.limit + c, 0, n - c);
    }
    ts.limit   += n;
    ts.buf_len += n;

    return 0;
}

int tokenizer_extract(TokenizerState &ts, token_s &t) {
    t.type = ts.token_type;
    
    switch (ts.token_type) {
    default:
        t.value = NULL;
        // if you want to keep the value, use
        // t.value = new std::string(ts.start, ts.cursor - ts.start);
        break;
    }

    // shrink the buffer to be space efficient
    int shift_back_len = 0;
    int cursor_offset = ts.cursor - ts.buf;
    while ((ts.buf_alloc - shift_back_len) > TOKENIZER_BUF_ALLOC &&
           (cursor_offset - shift_back_len) >=
           (ts.buf_alloc - shift_back_len) / 2) {
        shift_back_len += (ts.buf_alloc - shift_back_len) / 2;
    }

    if (shift_back_len > 0) {
        YYCTYPE *buf = (YYCTYPE *)malloc(sizeof(YYCTYPE) * (ts.buf_alloc - shift_back_len));
        if (buf) {
            cursor_offset -= shift_back_len;
            ts.buf_len    -= shift_back_len;
            ts.buf_alloc  -= shift_back_len;
            memcpy(buf + cursor_offset, ts.cursor, ts.limit - ts.cursor);
            free(ts.buf);
            ts.buf = buf;           
            ts.cursor = buf + cursor_offset;
            ts.limit  = buf + ts.buf_len;

        } else return -1;
    }

    return 0;
}

ast_node_s *ast_node_create(ASTParserState *state) {
    ast_node_s *node = new ast_node_s();
    state->pool.push_back(node);
    return node;
}

// main parser loop

void ast_node_delete(ast_node_s *node) {
    // process the resource deletion carefully
    delete node;
}

ASTParserState *parse_stream(std::istream &is) {
    TokenizerState s;
    tokenizer_init(s);
    s.is = &is;
    std::vector<token_s *> tokens;
    
    void *parser = ParserAlloc(malloc);

    ASTParserState *state = new ASTParserState();
    state->error = 0;
    state->result = NULL;
    
    while (true) {
        int t = tokenizer_scan(s);
        if (t) {
            state->error = 1;
            break;
        }

        token_s *token = new token_s();
        tokenizer_extract(s, *token);
        tokens.push_back(token);

        // send to parser
        Parser(parser, token->type, token, state);
        if (state->error) {
            break;
        } else
            // get the result after we reached eof
            if (token->type == T_EOF) {
                Parser(parser, 0, NULL, state);
            break;
        }
    }

    ParserFree(parser, free);

    tokenizer_destroy(s);
    for (int i = 0; i < tokens.size(); ++ i) {
        if (tokens[i]->value != NULL)
            delete tokens[i]->value;
        delete tokens[i];
    }

    if (state->error) {
        for (int i = 0; i < state->pool.size(); ++ i)
            ast_node_delete(state->pool[i]);
        delete state;
        return NULL;
    } else {
        return state;
    }
}
