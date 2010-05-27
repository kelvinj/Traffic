<?php

use pecs\Spec as Spec;
use Fu\Traffic as t;

describe("traffic", function() {
    before_each('reset_request');

    describe("rules", function() {
        it("should allow n nesting levels", function() {
            mimick_request('/one/two/three/four/five/six.seven', 'GET');
            $gather = gather_info(function () {
                t::rel('/:one', function (){
                    t::rel('/:two', function (){
                        t::rel('/:three', function (){
                            t::rel('/:four', function (){
                                t::rel('/:five', function (){
                                    t::get(':six.:seven', function ($p){
                                        unset($p['splats']);
                                        echo implode (':', $p);
                                    });
                                });
                            });
                        });
                    });
                });

                t::not_found (function(){echo 'no rules picked up';});
            });

            expect($gather)->to_be('one:two:three:four:five:six:seven');
        });


        it("should allow nesting with an http verb and /*", function() {
            mimick_request('/one/two/three/four/five/six.seven', 'GET');
            $gather = gather_info(function () {
                t::get('/:one/*', function (){
                    t::get('/:two/*', function (){
                        t::get('/*', function ($p){
                            printf('%s:%s:%s', $p['one'], $p['two'], $p['0']);
                        });
                    });
                });

                t::not_found (function(){echo 'no rules picked up';});
            });
            expect($gather)->to_be('one:two:three/four/five/six.seven');
        });


        it("should allow a matching route to pass onto the next matching route", function() {
            mimick_request('/page', 'GET');

            // just using pass();
            $gather = gather_info(function () {
                t::get('/page', function (){
                    echo '1.';
                    t::pass();
                });
                t::get('*', function (){
                    echo '2.';
                });

                t::not_found (function(){echo 'no rules picked up';});
            });
            expect($gather)->to_be('1.2.');

            reset_request();

            // using return pass();
            $gather = gather_info(function () {
                t::get('/page', function (){
                    echo '1.';
                    return t::pass();
                });
                t::get('*', function (){
                    echo '2.';
                });

                t::not_found (function(){echo 'no rules picked up';});
            });
            expect($gather)->to_be('1.2.');

            reset_request();

            // using return -1;
            $gather = gather_info(function () {
                t::get('/page', function (){
                    echo '1.';
                    return -1;
                });
                t::get('*', function (){
                    echo '2.';
                });

                t::not_found (function(){echo 'no rules picked up';});
            });
            expect($gather)->to_be('1.2.');
        });


        it("should allow path to be omitted (/ is assumed)", function() {

            // match home directory
            mimick_request('/', 'GET');
            $gather = gather_info(function () {
                t::get(function (){
                    echo '/';
                });

                t::not_found (function(){echo 'no rules picked up';});
            });
            expect($gather)->to_be('/');

            reset_request();

            // mimick get inside of rel()
            mimick_request('/login', 'GET');
            $gather = gather_info(function () {
                t::rel('/login', function(){
                    t::post(function (){
                        echo 'post';
                    });
                    t::get(function (){
                        echo 'get';
                    });
                });

                t::not_found (function(){echo 'no rules picked up';});
            });
            expect($gather)->to_be('get');

            reset_request();

            // mimick get inside of rel()
            mimick_request('/login', 'POST');
            $gather = gather_info(function () {
                t::rel('/login', function(){
                    t::get(function (){
                        echo 'get';
                    });
                    t::post(function (){
                        echo 'post';
                    });
                });

                t::not_found (function(){echo 'no rules picked up';});
            });
            expect($gather)->to_be('post');
        });


        it("should register global functions when requested", function() {
            $gather = gather_info(function () {
                t::register_global_functions();
                if (function_exists('get')) {
                    echo 'get()';
                }
            });
            expect($gather)->to_be('get()');
        });
    });
});
