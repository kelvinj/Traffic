<?php

use pecs\Spec as Spec;
use Fu\Traffic as t;

describe("traffic", function() {
    before_each('reset_request');

    describe("handles common HTTP protocols", function() {
        it("should run the correct rule according to the HTTP request", function() {
            // get
            mimick_request('/', 'GET');
            $gather = gather_info(function () {
                t::post('/', function(){echo 'post';});
                t::delete('/', function(){echo 'delete';});
                t::put('/', function(){echo 'put';});
                t::get('/', function () {echo 'get';});
            });
            expect($gather)->to_be('get');

            // post
            reset_request();
            mimick_request('/', 'POST');
            $gather = gather_info(function () {
                t::get('/', function () {echo 'get';});
                t::post('/', function(){echo 'post';});
            });
            expect($gather)->to_be('post');

            // put
            reset_request();
            mimick_request('/', 'PUT');
            $gather = gather_info(function () {
                t::get('/', function () {echo 'get';});
                t::put('/', function(){echo 'put';});
            });
            expect($gather)->to_be('put');

            // delete
            reset_request();
            mimick_request('/', 'DELETE');
            $gather = gather_info(function () {
                t::get('/', function () {echo 'get';});
                t::delete('/', function(){echo 'delete';});
            });
            expect($gather)->to_be('delete');
        });

        it("should accept PUT & DELETE requests using the _method hack", function() {
            // put
            mimick_request('/', 'PUT', true);
            $gather = gather_info(function () {
                t::post('/', function () {echo 'post';});
                t::put('/', function(){echo 'put';});
            });
            expect($gather)->to_be('put');

            // delete
            reset_request();
            mimick_request('/', 'DELETE', true);
            $gather = gather_info(function () {
                t::post('/', function () {echo 'post';});
                t::delete('/', function(){echo 'delete';});
            });
            expect($gather)->to_be('delete');
        });

        it("should ignore mixed nested rules, e.g. post() inside a get()", function() {
            // put
            mimick_request('/dir/path', 'GET');
            $gather = gather_info(function () {
                t::get('/dir/*', function () {
                    t::post('/path', function(){echo 'post only';});

                    echo 'get only';
                });

                t::not_found (function(){echo 'no rules picked up';});
            });
            expect($gather)->to_be('get only');
        });

        it("should allow request() to be called on any valid method", function() {
            // get
            mimick_request('/', 'GET');
            $gather = gather_info(function () {
                t::request('/', function () {
                    echo 'request';
                });

                t::get('/', function () {
                    echo 'get';
                });

                t::not_found (function(){echo 'no rules picked up';});
            });
            expect($gather)->to_be('request');

            reset_request();

            // get
            mimick_request('/', 'POST');
            $gather = gather_info(function () {
                t::request('/', function () {
                    echo 'request';
                });

                t::post('/', function () {
                    echo 'post';
                });

                t::not_found (function(){echo 'no rules picked up';});
            });
            expect($gather)->to_be('request');

            reset_request();

            // get
            mimick_request('/', 'PUT');
            $gather = gather_info(function () {
                t::request('/', function () {
                    echo 'request';
                });

                t::put('/', function () {
                    echo 'put';
                });

                t::not_found (function(){echo 'no rules picked up';});
            });
            expect($gather)->to_be('request');

            reset_request();

            // get
            mimick_request('/', 'DELETE');
            $gather = gather_info(function () {
                t::request('/', function () {
                    echo 'request';
                });

                t::delete('/', function () {
                    echo 'delete';
                });

                t::not_found (function(){echo 'no rules picked up';});
            });
            expect($gather)->to_be('request');
        });

        it("should ignore request() for unknown methods, e.g. not GET, POST.", function() {
            mimick_request('/', 'TRUNCATE');
            $gather = gather_info(function () {
                t::request('/', function () {
                    echo 'request';
                });

                t::not_found (function(){echo 'no rules picked up';});
            });
            expect($gather)->to_be('no rules picked up');
        });
    });
});
