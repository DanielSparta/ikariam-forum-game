<?php
    class Hangman{
        /* Hangman config details */
        //array of all the words and topics at the database table
        private array $all_db_table_words_and_topics;
        //number of guesses user used, starts at 0. each user have $used_guesses/$maximum_guesses
        private int $used_guesses;
        //number of maximum guesses user can guess
        private int $maximum_guesses;
        //number of how many words each user can get from the total words (prevent cheating)
        private int $words_per_user;
        
        /* Hangman current word details */
        //array of words and its topics available, each user get $words_per_user words, so this is the random $words_per_user words from the total word list
        private array $user_words_available;
        //index of current word&topic index to show from $user_words_available
        private int $current_word_index_from_available_words;
        //pdo instance
        private $pdo;

        public function __construct($used_guesses, $maximum_guesses, $words_per_user, $pdo){
            $this->used_guesses = $used_guesses;
            $this->maximum_guesses = $maximum_guesses;
            $this->words_per_user = $words_per_user;
            $this->pdo = $pdo;
        }

        public function setup_hangman(){
            $userState = $this->select_all_data_for_current_userid_from_hangmaneventuserstate_table();
            //if no row returned (first time user at hangman event) then insert the relevant values for him
            if ($userState === false) {
                //setting instance $all_db_table_words_and_topics value
                $this->set_all_words_and_topics_from_db_table();
                //getting instance $all_db_table_words_and_topics value
                $words = $this->get_all_words_and_topics_from_db_table();
                //based on $words_per_user, create a new value $user_words_available with current user topic and its words
                $user_words_available_keys = array_rand($words, $this->words_per_user);
                $current_word_index_from_available_words = $user_words_available_keys[array_rand($user_words_available_keys)];
                $user_words_available = array();
                foreach ($user_words_available_keys as $key) {
                    $user_words_available[] = $words[$key];
                }
                // Insert a new row if no existing state found for the user
                $stmt = $this->pdo->prepare("INSERT INTO hangman_event_user_state (user_id, current_word_index, used_guesses, maximum_guesses, remaining_words_array) 
                                       VALUES (?, ?, ?, ?, ?)");
                $maximum_guesses = 60;
                $stmt->execute([$_SESSION['user_id'], $currenet_word_index_from_available_words, $used_guesses, $maximum_guesses, serialize($user_words_available)]);
            } else {
                $user_words_available = unserialize($userStat["remaining_words_array"]);
                $this->set_hangman_config($userState["used_guesses"], $userState["maximum_guesses"]);
            }
            $this->set_user_config($current_word_index_from_available_words, $user_words_available);
        }

        public function change_current_word_index(){
            $this->current_word_index_from_available_words = rand(0, $this->user_words_available[$this->words_per_user]);
        }

        public function get_current_word_data()
        {
            return $this->user_words_available[$this->current_word_index_from_available_words];
        }

        private function set_hangman_config($used_guesses, $maximum_guesses){
            $this->used_guesses = $used_guesses;
            $this->maximum_guesses = $maximum_guesses;
            $this->current_word_index = $current_word_index;
        }

        private function set_user_config($current_word_index_from_available_words, $user_words_available)
        {
            $this->current_word_text_and_topic = $current_word_text_and_topic;
            $this->user_words_available = $user_words_available;
        }

        private function set_all_words_and_topics_from_db_table(){
            $stmt = $this->pdo->query("SELECT * FROM hangman_event_words");
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $words = array();
            foreach ($results as $row) {
                $words[] = [
                    'word_topic' => $row['word_topic'],  // Store word_topic
                    'word' => $row['word']               // Store word
                ];
            }
            $this->all_db_table_words_and_topics = $words;
        }

        private function get_all_words_and_topics_from_db_table(){
            return $this->all_db_table_words_and_topics;
        }

        private function select_all_data_for_current_userid_from_hangmaneventuserstate_table(){
            $stmt = $this->pdo->prepare("SELECT * FROM hangman_event_user_state WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $userState = $stmt->fetch(PDO::FETCH_ASSOC); // Fetch a single row
            return $userState;
        }
    }