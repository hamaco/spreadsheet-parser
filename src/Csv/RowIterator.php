<?php

namespace Akeneo\Component\SpreadsheetParser\Csv;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Row iterator for CSV
 *
 * The following options are available :
 *  - length:    the maximum length of read lines
 *  - delimiter: the CSV delimiter character
 *  - enclosure: the CSV enclosure character
 *  - escape:    the CSV escape character
 *  - encoding:  the encoding of the CSV file
 *
 * @author    Antoine Guigan <antoine@akeneo.com>
 * @copyright 2014 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class RowIterator implements \Iterator
{
    /**
     * @var string
     */
    protected $path;

    /**
     * @var array
     */
    protected $options;

    /**
     * @var resource
     */
    protected $fileHandle;

    /**
     * @var array
     */
    protected $currentKey;

    /**
     * @var array
     */
    protected $currentValue;

    /**
     * @var boolean
     */
    protected $valid;

    /**
     * Constructor
     *
     * @param string $path
     * @param array  $options
     */
    public function __construct(
        $path,
        array $options
    ) {
        $this->path = $path;
        $resolver = new OptionsResolver;
        $this->setDefaultOptions($resolver);
        $this->options = $resolver->resolve($options);
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        return $this->currentValue;
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return $this->currentKey;
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        $this->currentValue = fgetcsv(
            $this->fileHandle,
            $this->options['length'],
            $this->options['delimiter'],
            $this->options['enclosure'],
            $this->options['escape']
        );
        $this->currentKey++;
        $this->valid = (false !== $this->currentValue);
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        if ($this->fileHandle) {
            rewind($this->fileHandle);
        } else {
            $this->openResource();
        }
        $this->currentKey = 0;
        $this->next();
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        return $this->valid;
    }

    /**
     * Sets the default options
     *
     * @param OptionsResolverInterface $resolver
     */
    protected function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setOptional(['encoding']);
        $resolver->setDefaults(
            [
                'length'    => null,
                'delimiter' => ',',
                'enclosure' => '"',
                'escape'    => '\\'
            ]
        );
    }

    /**
     * Opens the file resource
     *
     * @return resource
     */
    protected function openResource()
    {
        $this->fileHandle = fopen($this->path, 'r');
        if (isset($this->options['encoding'])) {
            stream_filter_prepend(
                $this->fileHandle,
                sprintf(
                    "convert.iconv.%s/%s",
                    $this->options['encoding'],
                    $this->getCurrentEncoding()
                )
            );
        }
    }

    /**
     * Returns the server encoding
     *
     * @return string
     */
    protected function getCurrentEncoding()
    {
        $locale = explode('.', setlocale(LC_CTYPE, 0));

        return isset($locale[1]) ? $locale[1] : 'ASCII';
    }
}
