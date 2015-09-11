<?php

namespace IMS\TplBundle\Twig;

use IMS\CoreBundle\Component\File\UploadFilePath;

class Extension extends \Twig_Extension
{

    protected $_csrfProvider;
    
    protected $_doctrine;
    
    protected $_translator;

    public function __construct($csrfProvider, \Doctrine\Bundle\DoctrineBundle\Registry $doctrine, $translator)
    {
        $this->_csrfProvider = $csrfProvider;
        $this->_doctrine = $doctrine;
        $this->_translator = $translator;
    }

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('youtube_embed', array($this, 'youtubeEmbed')),
            new \Twig_SimpleFilter('excerpt', array($this, 'excerptFilter')),
            new \Twig_SimpleFilter('rating_stars', array($this, 'ratingToStars')),
            new \Twig_SimpleFilter('relative_date', array($this, 'relativeDate')),
        );
    }

    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('csrf_token', array($this, 'csrfToken')),
        );
    }

    /**
     * Convert youtube url to embed url
     * @param string $url
     */
    public function youtubeEmbed($url)
    {
        // Try to retreive the code from inside the url
        preg_match("#^(?:https?://)?(?:www\.)?(?:youtu\.be/|youtube\.com(?:/embed/|/v/|/watch\?v=|/watch\?.+&v=))([\w-]{11})(?:.+)?$#x", $url, $matches);

        // check if the url contains video code
        if (isset($matches[1]))
            return '//www.youtube.com/embed/' . $matches[1];

        return NULL;
    }

    /**
     * Text excerpt filter:
     *      - removes all html tags
     *      - cuts string by length. Default length 150
     * 
     * @param string $string
     * @param integer $str_length
     * @return string
     */
    public function excerptFilter($string, $str_length = 150)
    {
        $result_string = substr(strip_tags($string), 0, $str_length);
        
        // add three dots, if passed string greater than cut length
        return strlen($string) > $str_length ? $result_string.'...' : $result_string;
    }
        
        /**
         * Separate passed text on 'visible' and 'hidden' parts
         */
        $visibleText = substr($text, 0, $str_length);
        $hiddenText = substr($text, $str_length, strlen($text) - $str_length);
        
        return '<p class="clear narrowed-text">'.$visibleText.'<span class="ellipses">...</span><span class="hidden-text" style="display: none;">'.$hiddenText.'</span><a class="more-link" href="">'.$this->_translator->trans('more').'</a></p>';
    }

    /**
     * Convert float rating (3.75) to string stars (3-5) to use in css class
     * @param float $rating
     */
    public function ratingToStars($rating)
    {
        $val = round($rating * 2) / 2;

        $pieces = explode('.', $val);

        return $pieces[0] . (isset($pieces[1]) ? "-{$pieces[1]}" : "");
    }

    /**
     * Build a csrf token
     * @param string $intension
     * @return string
     */
    public function csrfToken($intension)
    {
        return $this->_csrfProvider->generateCsrfToken($intension);
    }

    /**
     * Build a relative date
     * @param mixed[\MongoDate|\DateTime] $date
     * @return string
     */
    public function relativeDate($date)
    {
        if ($date instanceof \MongoDate)
            $date = new \DateTime('@' . $date->sec);
        
        $now = new \DateTime;
        $diff = $now->diff($date);
        $seconds = $diff->days * 86400 + $diff->h * 3600 + $diff->i * 60 + $diff->s;

        if (0 < $seconds && $seconds < 60)
            return $diff->format('now');

        if (60 <= $seconds && $seconds < 120)
            return $diff->format('%i minute ago');

        if (120 <= $seconds && $seconds < 3600)
            return $diff->format('%i minutes ago');

        if (3600 <= $seconds && $seconds < 7200)
            return $diff->format('%h hour ago');

        if (7200 <= $seconds && $seconds < 86400)
            return $diff->format('%h hours ago');

        return $date->format('M d, Y - h:i a');
    }

    public function getName()
    {
        return 'ims_tpl_ext';
    }
}
