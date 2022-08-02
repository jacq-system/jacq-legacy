<?php

//------------------------------------------------------------------------------
// annoquery
// ---------
// description:
//   This library provides an easy way to fetch annotations from an AnnoSys
//   service.
// author: Felix Hilgerdenaar
// last modification: 2014-09-15 Updated
//------------------------------------------------------------------------------

namespace Jacq;

class TripleID
{
    protected $institutionID;
    protected $sourceID;
    protected $objectID;

    public function __construct($inst, $src, $obj)
    {
        $this->institutionID = $inst;
        $this->sourceID = $src;
        $this->objectID = $obj;
    }

    public function getInst()
    {
        return $this->institutionID;
    }

    public function getSrc()
    {
        return $this->sourceID;
    }

    public function getObj()
    {
        return $this->objectID;
    }

    public function toString()
    {
        return urlencode($this->institutionID)
            . "/" . urlencode($this->sourceID)
            . "/" . urlencode($this->objectID);
    }
}
