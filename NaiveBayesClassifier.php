<?php

    /**
     * @author Varun Kumar <varunon9@gmail.com>
     */
    
	
	function object_array_contains($arr, $property, $item) {
		return count(
			array_filter(
				$arr, 
				function($arr_item) use ($item) { 
					return $arr_item[$property] == $item; 
				}
			)
		);
	}

	/**
	 * Use binary search to find a key of a value in an array.
	 *
	 * @param array $array
	 *   The array to search for the value.
	 * @param int $value
	 *   A value to be searched.
	 *
	 * @return int|null
	 *   Returns the key of the value in the array, or null if the value is not found.
	 * 
	 * @src https://www.hashbangcode.com/article/implementation-array-binary-search-php
	 */
	function binarySearch($array, $value) {
		// Set the left pointer to 0.
		$left = 0;
		// Set the right pointer to the length of the array -1.
		$right = count($array) - 1;
	
		while ($left <= $right) {
		// Set the initial midpoint to the rounded down value of half the length of the array.
		$midpoint = (int) floor(($left + $right) / 2);
	
		if ($array[$midpoint] < $value) {
			// The midpoint value is less than the value.
			$left = $midpoint + 1;
		} elseif ($array[$midpoint] > $value) {
			// The midpoint value is greater than the value.
			$right = $midpoint - 1;
		} else {
			// This is the key we are looking for.
			return $midpoint;
		}
		}
		// The value was not found.
		return NULL;
	}

    class NaiveBayesClassifier {

		public $stopWords = [];

    	public function __construct() {
			require('db_connect.php');
			$stmt = $pdo->prepare('SELECT word FROM stopwords ORDER BY word ASC');
			$stmt->execute();
			$this->stopWords = array_map(function($row) {
				return $row['word'];
			}, $stmt->fetchAll());
    	}

        /**
         * sentence is text(document) which will be categorized
         * @return category
         */
    	public function classify($sentence) {

    		// extracting keywords from input text/sentence
    		$keywordsArray = $this -> tokenize($sentence);

    		// classifying the category
    		$category = $this -> decide($keywordsArray);

    		return $category;
    	}

    	/**
    	 * @sentence- text/document provided by user as training data
    	 * @category- category of sentence
    	 * this function will save sentence aka text/document in trainingSet table with given category
    	 * It will also update count of words (or insert new) in wordFrequency table
    	 */
		public function train($sentence, $category) {
			require('db_connect.php');
			$stmt = $pdo->prepare('SELECT * FROM categories WHERE NAME = :category');
			$stmt->execute([':category' => $category]);

			if ($stmt->rowCount() > 0) {
				$category_object = $stmt->fetch();
				$stmt = $pdo->prepare('INSERT INTO trainingSet (document, category_id) VALUES (:sentence, :category_id)');
				$stmt->execute([':sentence' => $sentence, ':category_id' => $category_object['ID']]);

	    	    // extracting keywords
	    	    $keywordsArray = $this -> tokenize($sentence);

	    	    // updating wordFrequency table
	    	    foreach ($keywordsArray as $word) {

					$stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM wordFrequency WHERE word = :word AND category_id = :category_id");
					$stmt->execute([':word' => $word, ':category_id' => $category_object['ID']]);
					// $count = mysqli_fetch_assoc($sql);
					$count = $stmt->fetch();

	    	    	if ($count['total'] == 0) {
						// $sql = mysqli_query($conn, "INSERT into wordFrequency (word, category, count) values('$word', '$category', 1)");
						$stmt = $pdo->prepare('INSERT INTO wordFrequency (word, category_id, count) VALUES (:word, :category_id, 1)');
						$stmt->execute([':word' => $word, ':category_id' => $category_object['ID']]);
	    	    	} else {
	    	    		// $sql = mysqli_query($conn, "UPDATE wordFrequency set count = count + 1 where word = '$word'");
						$stmt = $pdo->prepare('UPDATE wordFrequency SET count = count + 1 WHERE word = :word');
						$stmt->execute([':word' => $word]);
	    	    	}
	    	    }

    		} else {
    			throw new Exception('Unknown category');
    		}
    	}

    	/**
    	 * this function takes a paragraph of text as input and returns an array of keywords.
    	 */

    	private function tokenize($sentence) {

	    	//removing all the characters which ar not letters, numbers or space
	    	$sentence = preg_replace("/[^a-zA-Z 0-9]+/", "", $sentence);

	    	//converting to lowercase
	    	$sentence = strtolower($sentence);

	        //an empty array
	    	$keywordsArray = array();

	    	//splitting text into array of keywords
	        //http://www.w3schools.com/php/func_string_strtok.asp
	    	$token =  strtok($sentence, " ");
	    	while ($token !== false) {

				//excluding elements which are present in stopWords array
				//http://www.w3schools.com/php/func_array_in_array.asp
				if (!binarySearch($this->stopWords, $token)) {
					array_push($keywordsArray, $token);
				}
		    	$token = strtok(" ");
	    	}
	    	return $keywordsArray;
    	}

    	/**
    	 * This function takes an array of words as input and return category (spam/ham) after
    	 * applying Naive Bayes Classifier
    	 *
    	 * Naive Bayes Classifier Algorithm -
    	 *
    	 *   p(spam/bodyText) = p(spam) * p(bodyText/spam) / p(bodyText);
    	 *   p(ham/bodyText) = p(ham) * p(bodyText/ham) / p(bodyText);
    	 *   p(bodyText) is constant so it can be ommitted
    	 *   p(spam) = no of documents (sentence) belonging to category spam / total no of documents (sentence)
    	 *   p(bodyText/spam) = p(word1/spam) * p(word2/spam) * .... p(wordn/spam)
    	 *   Laplace smoothing for such cases is usually given by (c+1)/(N+V), 
    	 *   where V is the vocabulary size (total no of different words)
    	 *   p(word/spam) = no of times word occur in spam / no of all words in spam
    	 *   Reference:
    	 *   http://stackoverflow.com/questions/9996327/using-a-naive-bayes-classifier-to-classify-tweets-some-problems
    	 *   https://github.com/ttezel/bayes/blob/master/lib/naive_bayes.js
    	*/
    	private function decide ($keywordsArray) {
    		$category = 'ham';

    		// making connection to database
    	    require('db_connect.php');

			$stmt = $pdo->prepare('SELECT categories.name, categories.ID, COUNT(*) as total FROM trainingSet left join categories on (trainingset.category_id = categories.ID) GROUP BY categories.ID');
			$stmt->execute();
			$counts = $stmt->fetchAll();
			$totalCount = 0;
			foreach ($counts as $count) {
				$totalCount += $count['total'];
			}
			
			$stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM wordFrequency');
			$stmt->execute();
			$distinctWords = $stmt->fetch()['total'];

			$highestProbability = PHP_FLOAT_MIN;
			foreach ($counts as $count) {
				// $p = $count['total'] / $totalCount;
				$p = 0;
				// $probability = log($p);
				$probability = $p;
				foreach ($keywordsArray as $word) {
					$stmt = $pdo->prepare('SELECT count AS total FROM wordFrequency WHERE word = :word AND category_id = :category_id');
					$stmt->execute([':word' => $word, ':category_id' => $count['ID']]);
					if ($stmt->rowCount() == 0) continue;
					$wordCount = $stmt->fetch()['total'];
					// $probability += log(($wordCount + 1) / ($count['total'] + $distinctWords));
					$probability += ($wordCount + 1) / ($count['total'] + $distinctWords);
					// echo '[' . $count['ID'] . ']' . $count['name'] . ': ' . $wordCount . ' + 1 / (' . $count['total'] . ' + ' . $distinctWords . ') = ' . $probability . '<br/>';
				}
				if ($probability >= $highestProbability) {
					$highestProbability = $probability;
					$category = $count['name'];
				}
				// echo $count['name'] . ': ' . $probability . '<br/>';
			}

    		return $category;
    	}
    }

?>
