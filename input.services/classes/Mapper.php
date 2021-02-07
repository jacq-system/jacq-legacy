<?php

abstract class Mapper
{
    /**
     * @var mysqli
     */
    protected $db;

    public function __construct(mysqli $db)
    {
        $this->db = $db;
    }
}