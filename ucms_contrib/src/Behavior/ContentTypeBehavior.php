<?php

namespace MakinaCorpus\Ucms\Contrib\Behavior;

class ContentTypeBehavior implements ContentTypeBehaviorInterface
{
    /**
     * @var string
     */
    private $identifier;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $description;

    /**
     * ContentTypeBehavior constructor.
     *
     * @param string $identifier
     */
    public function __construct($identifier)
    {
        $identifier = trim($identifier);

        if (strlen($identifier) == 0) {
            $message = "Behavior's identifier can't be empty.";
            throw new \LengthException($message);
        }
        if (strlen($identifier) > 32) {
            $message = "Behavior's identifier must contain at most 32 characters (\"%s\": %d characters).";
            throw new \LengthException(sprintf($message, $identifier, strlen($identifier)));
        }

        $this->identifier = $identifier;
    }

    /**
     * @inheritdoc
     */
    public function getId()
    {
        return $this->identifier;
    }

    /**
     * Sets the behavior's name.
     *
     * @param string $name
     *
     * @return ContentTypeBehavior
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets the behavior's description.
     *
     * @param string $description
     *
     * @return ContentTypeBehavior
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getDescription()
    {
        return $this->description;
    }
}
