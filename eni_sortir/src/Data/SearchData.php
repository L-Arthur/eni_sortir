<?php

namespace App\Data;
use App\Entity\Participant;
use App\Entity\Campus;
use DateTime;
use DateTimeInterface;
use Symfony\Component\Form\Extension\Core\Type\DateType;

class SearchData
{
    /**
     * @var string
     */
    public $q = '';


    /**
     * @var campus
     */
    public $campuses;


    /**
     * @var null | DateTimeInterface
     */
    public $dateMin = null;


    /**
     * @var null | DateTimeInterface
     */
    public $dateMax = null;


    /**
     * @var array | null
     */
    public $criteres;

    /**
     * @return mixed
     */
    public function getCampuses()
    {
        return $this->campuses;
    }

    /**
     * @param mixed $campuses
     */
    public function setCampuses($campuses): void
    {
        $this->campuses = $campuses;
    }

}