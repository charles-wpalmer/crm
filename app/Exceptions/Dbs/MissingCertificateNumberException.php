<?php

namespace App\Exceptions\Dbs;

use App\Models\EducationCandidate;

class MissingCertificateNumberException extends DbsUpdateServiceException
{
    public function __construct(EducationCandidate $candidate)
    {
        parent::__construct('EducationCandidate does not have a DBS certificate number to check.', $candidate);
    }
}
