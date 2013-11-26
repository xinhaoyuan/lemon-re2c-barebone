%name Parser

%extra_argument {ASTParserState *state}
%token_type     {token_s *}
%default_type   {ast_node_s *}

%include {
#include "driver.hpp"
}

%syntax_error {
state->error = 1;
}

target                ::= /* ... */ T_EOF
. { // fill in the result
    state->result = NULL; }
