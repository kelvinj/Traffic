<?php

use pecs\Spec as Spec;
use Fu\Traffic as t;

describe("traffic", function() {
    before_each('reset_request');

    describe("path formatting", function() {
        it("should ignore trailing slashes", function() {

            // path has trailing slash but not route
            mimick_request('/login/', 'GET');
            $gather = gather_info(function () {
                t::get('/login', function ($p) {
                    echo 'login';
                });
                t::not_found (function(){echo 'no rules picked up';});
            });
            expect($gather)->to_be('login');

            reset_request();

            // route has trailing slash but not path
            mimick_request('/login', 'GET');
            $gather = gather_info(function () {
                t::get('/login/', function ($p) {
                    echo 'login';
                });
                t::not_found (function(){echo 'no rules picked up';});
            });
            expect($gather)->to_be('login');

            reset_request();

            // both path and route have trailing slashes
            mimick_request('/login/', 'GET');
            $gather = gather_info(function () {
                t::get('/login/', function ($p) {
                    echo 'login';
                });
                t::not_found (function(){echo 'no rules picked up';});
            });
            expect($gather)->to_be('login');
        });

        it("should allow wildcards at the front of the string", function() {
            mimick_request('/path/to/file.html', 'GET');
            $gather = gather_info(function () {
                t::get('*.html', function ($p) {
                    echo $p[0];
                });
            });
            expect($gather)->to_be('/path/to/file');
        });

        it("should allow wildcards in the middle of the string", function() {
            mimick_request('/path/to/file.html', 'GET');
            $gather = gather_info(function () {
                t::get('/path/*.html', function ($p) {
                    echo $p[0];
                });
            });
            expect($gather)->to_be('to/file');
        });

        it("should allow wildcards at the end of the string", function() {
            mimick_request('/path/to/file.html', 'GET');
            $gather = gather_info(function () {
                t::get('/path/to/*', function ($p) {
                    echo $p[0];
                });
            });
            expect($gather)->to_be('file.html');
        });

        it("should match and return names params", function() {
            mimick_request('/path/to/file.html', 'GET');
            $gather = gather_info(function () {
                t::get('/path/to/:file.html', function ($p) {
                    echo $p['file'];
                });
            });
            expect($gather)->to_be('file');
        });

        it("should ignores rules with named params that do not match the URI", function() {
            mimick_request('/path/to', 'GET');
            $gather = gather_info(function () {
                t::get('/path/to/:file', function ($p) {
                    echo $p['file'];
                });
                t::not_found (function(){echo 'no rules picked up';});
            });
            expect($gather)->to_be('no rules picked up');
        });

        it("should match rules with optional named params that match the URI", function() {
            mimick_request('/path/to/file.html', 'GET');
            $gather = gather_info(function () {
                t::get('/path/to(/:file)', function ($p) {
                    echo $p['file'];
                });
                t::not_found (function(){echo 'no rules picked up';});
            });
            expect($gather)->to_be('file.html');
        });

        it("should match rules with optional named params that DO NOT match the URI", function() {
            mimick_request('/path/to', 'GET');
            $gather = gather_info(function () {
                t::get('/path/to(/:file)', function ($p) {
                    echo 'rule picked up';
                });
                t::not_found (function(){echo 'no rules picked up';});
            });
            expect($gather)->to_be('rule picked up');
        });

        it("should allow and return multiple wildcard entries", function() {
            mimick_request('/path/to/file/download.html', 'GET');
            $gather = gather_info(function () {
                t::get('/path/*/*/*.html', function ($p) {
                    printf('rule picked up with %d wildcards', count($p['splats']));
                });
                t::not_found (function(){echo 'no rules picked up';});
            });
            expect($gather)->to_be('rule picked up with 3 wildcards');
        });

        it("should allow and return mixture of wildcards and named params", function() {
            mimick_request('/path/to/file/download.html', 'GET');
            $gather = gather_info(function () {
                t::get('/:root/*/*/:script.:format', function ($p) {
                    printf('rule picked up with %d wildcards, root=%s, script=%s, format=%s',
                            count($p['splats']), $p['root'], $p['script'], $p['format']);
                });
                t::not_found (function(){echo 'no rules picked up';});
            });
            expect($gather)->to_be('rule picked up with 2 wildcards, root=path, script=download, format=html');
        });

        it("should allow and return mixture of wildcards and named params in a nested call", function() {
            mimick_request('/path/to/file/download.html', 'GET');
            $gather = gather_info(function () {
                t::rel('/:root', function (){
                    t::get('/*/*/:script.:format', function ($p) {
                        printf('rule picked up with %d wildcards, root=%s, script=%s, format=%s',
                                count($p['splats']), $p['root'], $p['script'], $p['format']);
                    });
                });
                t::not_found (function(){echo 'no rules picked up';});
            });
            expect($gather)->to_be('rule picked up with 2 wildcards, root=path, script=download, format=html');
        });

        it("should allow commas in paths, relative paths and nested paths", function() {
            mimick_request('/path/to/file/1,2,3', 'GET');
            $gather = gather_info(function () {
                t::get('/path/to/file/*,*,*', function ($p){
                    printf('%d wildcards. %s, %s, %s',
                                count($p['splats']), $p[0], $p[1], $p[2]);
                });
                t::not_found (function(){echo 'no rules picked up';});
            });
            expect($gather)->to_be('3 wildcards. 1, 2, 3');

            reset_request();

            mimick_request('/path/to/file/1,2,3;edit', 'GET');
            $gather = gather_info(function () {
                t::get('/path/to/file/*,*,*;edit', function ($p){
                    printf('%d wildcards. %s, %s, %s',
                                count($p['splats']), $p[0], $p[1], $p[2]);
                });
                t::not_found (function(){echo 'no rules picked up';});
            });

            expect($gather)->to_be('3 wildcards. 1, 2, 3');
        });
    });
});
