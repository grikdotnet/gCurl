<?php
/**
 * Provides typehinting for request classes
 */

namespace grikdotnet\curl;


interface IRequest {

    public function getURI();
    public function getCookieString();
    public function getHeaders();
    public function addCookie($name,$value);
    public function addHeader($name,$value='');
    public function prepare(Options $options);
    public function onRequestEnd();
}