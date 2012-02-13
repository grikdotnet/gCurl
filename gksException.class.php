<?php
/**
 * The interface defines the methods that prepare the log message, HTML-formatted message
 *
 */
interface gksException{
    /**
     * Prepare the message to write to the log
     *
     * @return  string
     */
    public function getLogMessage();
    
    /**
     * returns the exception dump prepared for a browser
     * 
     * @return string
     */
    public function getHtmlMessage();

    /* native exception methods */
    function getCode();
    function getFile();
    function getLine();
    function getMessage();
    function getTrace();
    function getTraceAsString();
}

