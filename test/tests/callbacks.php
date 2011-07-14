<?php
use pecs\Spec as Spec;
use Fu\Traffic as t;

describe("traffic", function() {
    before_each('reset_request');

    describe("handling different types of callback", function() {
        it("should run callback on a static class", function() {
            mimick_request('/', 'GET');
            $gather = gather_info(function () {
                t::get('/', array('CallbackTestClass', 'index'));
            });
            expect($gather)->to_be('index.');
        });

        it("should run callback on an instantiated object", function() {
            mimick_request('/', 'GET');
            $gather = gather_info(function () {
                t::get('/', array(new CallbackTestClass, 'index'));
            });
            expect($gather)->to_be('index.');
        });

        it("should run callback with hooks if available on an instantiated object", function() {
            mimick_request('/', 'GET');
            $gather = gather_info(function () {
                t::get('/', array(new CallbackTestClassWithHooks, 'index'));
            });
            expect($gather)->to_be('before.index.after.');
        });

        it("should not run hooks on a static class", function() {
            mimick_request('/', 'GET');
            $gather = gather_info(function () {
                t::get('/', array('CallbackTestClassWithHooks', 'index'));
            });
            expect($gather)->to_be('index.');
        });

        it("should allow running multiple callbacks for 1 route", function() {
            mimick_request('/', 'GET');
            $gather = gather_info(function () {
                t::get('/', array( array(new CallbackTestClass, 'index'), array(new CallbackTestClassWithHooks, 'index') ));
            });
            expect($gather)->to_be('index.before.index.after.');
        });

        it("should accept string representations of objects", function() {
            mimick_request('/', 'GET');
            $gather = gather_info(function () {
                t::get('/', array('CallbackTestClass->index', 'CallbackTestClassWithHooks->index' ) );
            });
            expect($gather)->to_be('index.before.index.after.');
        });

        it("should accept string representations of classes", function() {
            mimick_request('/', 'GET');
            $gather = gather_info(function () {
                t::get('/', array('CallbackTestClass::index', 'CallbackTestClassWithHooks::index' ) );
            });
            expect($gather)->to_be('index.index.');
        });

        it("should accept a mixture of all the ways one can define a callback", function() {
            mimick_request('/', 'GET');
            $gather = gather_info(function () {
                t::get('/',
                    array(
                        'CallbackTestClass::index',
                        'CallbackTestClassWithHooks->index',
                        array(CallbackTestClass, 'index'),
                        array(new CallbackTestClassWithHooks, 'index')
                    )
                );
            });
            expect($gather)->to_be('index.before.index.after.index.before.index.after.');
        });

        it("should accept a string representation of a callback", function() {
            mimick_request('/', 'GET');
            $gather = gather_info(function () {
                t::get('/', 'CallbackTestClass::index, CallbackTestClassWithHooks->index');
            });
            expect($gather)->to_be('index.before.index.after.');
        });


    });
});


class CallbackTestClass {
    function index () {
        echo 'index.';
    }
}
class CallbackTestClassWithHooks {
    function before_route () {
        echo 'before.';
    }
    function index () {
        echo 'index.';
    }
    function after_route () {
        echo 'after.';
    }
}