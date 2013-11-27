#ifndef __DRIVER_HPP__
#define __DRIVER_HPP__

#include <iostream>
#include <vector>
#include <assert.h>

// TOKENIZER ==================================================

#define YYCTYPE  char

#define TOKENIZER_BUF_ALLOC 16

struct TokenizerState {
    // Global state
    int      buf_alloc;
    int      buf_len;
    YYCTYPE *buf;
    YYCTYPE *limit;
    YYCTYPE *cursor;
    std::istream *is;
    // Per token state
    int      token_type;
    YYCTYPE *marker;
    YYCTYPE *start;
};

struct token_s {
    int type;
    std::string *value;
};

int tokenizer_scan(TokenizerState &ss);
int tokenizer_fill(TokenizerState &ts, int n);

// PARSER =====================================================

struct ast_node_s {
    // fill your content
};

struct ASTParserState {
    int error;
    std::vector<ast_node_s *> pool;
    ast_node_s *result;
};

// used in parser
ast_node_s *ast_node_create(ASTParserState *state);

// defined in parser
void *ParserAlloc(void *(*mallocProc)(size_t));
void  ParserFree(void *p, void (*freeProc)(void*));
void  ParserTrace(FILE *TraceFILE, char *zTracePrompt);
void  Parser(void *yyp, int yymajor, token_s *token, ASTParserState *state);

#endif
