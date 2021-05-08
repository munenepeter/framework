<?php
//require all files here

//function for redirecting 

function view($name){

    return require "views/{$name}.view.php";
}