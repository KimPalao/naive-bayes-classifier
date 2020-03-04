<?php

    /**
     * mysql> create database naiveBayes;
     * mysql> use naiveBayes;
     * mysql> create table trainingSet (S_NO integer primary key auto_increment, document text, category varchar(255));
     * mysql> create table wordFrequency (S_NO integer primary key auto_increment, word varchar(255), count integer, category varchar(255));
     */

    require_once('NaiveBayesClassifier.php');

        $classifier = new NaiveBayesClassifier();

        $classifier->train('Hello! We are looking for a sponsor', 'sponsor');
        $classifier->train('hm?', 'price');
        $classifier->train('how much?', 'price');

        $classifier->train('Hello', 'ham');
//     $classifier -> train('Hi', 'ham');

        foreach (['hello, sponsor?', 'hello', 'hello, how much is this?'] as $sentence) {                
                $category = $classifier->classify($sentence);
                echo $sentence . ': ' . $category . '<br/>';
        }
    
//     $category = $classifier -> classify('Re: Applying for Fullstack Developer');
//     echo $category;

?>