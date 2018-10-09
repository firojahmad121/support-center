<?php

namespace Webkul\UVDesk\SupportCenterBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TranslatedArticleHistory
 */
class TranslatedArticleHistory
{
    /**
     * @var integer
     */
    private $id;



    /**
     * @var integer
     */
    private $translatedArticleId;

    /**
     * @var integer
     */
    private $userId;

    /**
     * @var string
     */
    private $content;

    /**
     * @var \DateTime
     */
    private $dateAdded;


    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

   
    /**
     * Set translatedArticleId
     *
     * @param integer $translatedArticleId
     * @return TranslatedArticleHistory
     */
    public function setTranslatedArticleId($translatedArticleId)
    {
        $this->translatedArticleId = $translatedArticleId;

        return $this;
    }

    /**
     * Get translatedArticleId
     *
     * @return integer 
     */
    public function getTranslatedArticleId()
    {
        return $this->translatedArticleId;
    }

    /**
     * Set userId
     *
     * @param integer $userId
     * @return TranslatedArticleHistory
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get userId
     *
     * @return integer 
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set content
     *
     * @param string $content
     * @return TranslatedArticleHistory
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content
     *
     * @return string 
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set dateAdded
     *
     * @param \DateTime $dateAdded
     * @return TranslatedArticleHistory
     */
    public function setDateAdded($dateAdded)
    {
        $this->dateAdded = $dateAdded;

        return $this;
    }

    /**
     * Get dateAdded
     *
     * @return \DateTime 
     */
    public function getDateAdded()
    {
        return $this->dateAdded;
    }

    /**
     * @ORM\PrePersist
     */
    public function setCreatedAtValue()
    {
        $this->dateAdded = new \DateTime();
    }
}
