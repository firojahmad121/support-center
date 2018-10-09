<?php

namespace Webkul\UVDesk\SupportCenterBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TranslatedArticle
 */
class TranslatedArticle
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $locale;



    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $content;

    /**
     * @var string
     */
    private $metaDescription;

    /**
     * @var string
     */
    private $keywords;

    /**
     * @var string
     */
    private $metaTitle;

    /**
     * @var \Webkul\SupportCenterBundle\Entity\Article
     */
    private $article;


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
     * Set locale
     *
     * @param string $locale
     * @return TranslatedArticle
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * Get locale
     *
     * @return string 
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return TranslatedArticle
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set content
     *
     * @param string $content
     * @return TranslatedArticle
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
     * Set metaDescription
     *
     * @param string $metaDescription
     * @return TranslatedArticle
     */
    public function setMetaDescription($metaDescription)
    {
        $this->metaDescription = $metaDescription;

        return $this;
    }

    /**
     * Get metaDescription
     *
     * @return string 
     */
    public function getMetaDescription()
    {
        return $this->metaDescription;
    }

    /**
     * Set keywords
     *
     * @param string $keywords
     * @return TranslatedArticle
     */
    public function setKeywords($keywords)
    {
        $this->keywords = $keywords;

        return $this;
    }

    /**
     * Get keywords
     *
     * @return string 
     */
    public function getKeywords()
    {
        return $this->keywords;
    }

    /**
     * Set metaTitle
     *
     * @param string $metaTitle
     * @return TranslatedArticle
     */
    public function setMetaTitle($metaTitle)
    {
        $this->metaTitle = $metaTitle;

        return $this;
    }

    /**
     * Get metaTitle
     *
     * @return string 
     */
    public function getMetaTitle()
    {
        return $this->metaTitle;
    }

    /**
     * Set article
     *
     * @param \Webkul\SupportCenterBundle\Entity\Article $article
     * @return TranslatedArticle
     */
    public function setArticle(\Webkul\SupportCenterBundle\Entity\Article $article = null)
    {
        $this->article = $article;

        return $this;
    }

    /**
     * Get article
     *
     * @return \Webkul\SupportCenterBundle\Entity\Article 
     */
    public function getArticle()
    {
        return $this->article;
    }

    /**
     * Set possible translate article details
     *
     */
    public function setDetails($data)
    {
        foreach ($data as $field => $value) {
            $method = sprintf('set%s', ucwords($field));
            if(property_exists($this,$field) && !in_array($field, ['company', 'article', 'locale'])) {
                $this->$method($value);
            }
        }
    }
}
