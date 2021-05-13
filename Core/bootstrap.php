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
//Instead of this approach of a single function
//Just write a class a real request class for 
//Which will be accessible to all controllers 
//From the base controller and will validate the 
//Form input
//Maybe I'll add the function inside of controllers
