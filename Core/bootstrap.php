<?php
//require all files here

//function for redirecting 

function view($name){

    return require "views/{$name}.view.php";
}

function validate ($formData){
    if(empty ($formData){
     return "{$formData} cannot be empty";
     }
   //Add trim
   //Htmlspecialchars
   //Others
   
   Return $formData;
}
