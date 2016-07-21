<?php
    class PublicPrivateException extends Exception
    {
        protected $private_message;

        public function __construct( $public_message, $private_message, $code )
        {
            parent::__construct($public_message, $code);
            $this->private_message = $private_message;
        }

        public function getPrivateMessage()
        {
            return $this->private_message;
        }
    }
