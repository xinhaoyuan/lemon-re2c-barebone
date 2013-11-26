.PHONY: all clean test

all: bin/test

CC      := gcc
CCFLAGS := -O0 -g -I ./
CXX     := g++

gen:
	mkdir -p gen

gen/parser.cpp: parser.y bin/lemon gen
	cd gen && cp ../parser.y . && ../bin/lemon T=../tool/lempar.c parser.y && mv parser.c parser.cpp

gen/tokenizer.cpp: tokenizer.re gen
	re2c -i -F -o $@ $<

gen/driver.cpp: driver.cpp.php gen
	php $< > $@

bin:
	mkdir -p bin

bin/lemon: tool/lemon.c tool/lempar.c bin
	${CC} ${CCFLAGS} $< -o $@

bin/parser.o: gen/parser.cpp bin
	${CXX} ${CCFLAGS} -c $< -o $@

bin/tokenizer.o: gen/tokenizer.cpp driver.hpp gen/parser.cpp bin
	${CXX} ${CCFLAGS} -c $< -o $@

bin/driver.o: gen/driver.cpp driver.hpp gen/parser.cpp bin
	${CXX} ${CCFLAGS} -c $< -o $@

bin/test: test.cpp bin/driver.o bin/tokenizer.o bin/parser.o bin
	${CXX} ${CCFLAGS} test.cpp bin/driver.o bin/tokenizer.o bin/parser.o -o $@

clean:
	-rm -r bin gen
