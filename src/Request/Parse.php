<?php


namespace Hooklife\SwooleDockerApi\Request;


use Hooklife\SwooleDockerApi\Request\Exceptions\NeedMoreDataException;
use Hooklife\SwooleDockerApi\Request\Exceptions\ParseException;

class Parse
{
    const DEFAULT_MAX_HEADER_BYTES = 8192;
    const DEFAULT_MAX_BODY_BYTES = 10485760;

    const STATUS_LINE_PATTERN = "#^
        HTTP/(?P<protocol>\d+\.\d+)[\x20\x09]+
        (?P<status>[1-5]\d\d)[\x20\x09]*
        (?P<reason>[^\x01-\x08\x10-\x19]*)
    $#ix";


    private $buffer;
    private $maxHeaderBytes = self::DEFAULT_MAX_HEADER_BYTES;
    private $maxBodyBytes = self::DEFAULT_MAX_BODY_BYTES;


    private $rawSplited = [];


    private $protocol;
    /**
     * @var string
     */
    private $statusReason;
    /**
     * @var int
     */
    private $statusCode;
    private $chunkLenRemaining;
    /**
     * @var int
     */
    private $bodyBytesConsumed;
    /**
     * @var string
     */
    private $body;


    public function parse()
    {
        if ($this->buffer == '') {
            return false;
        }

        if (!$statusAndHeadersLines = $this->shiftHeadersFromMessageBuffer()) {
            return false;
        }

        $this->splitStatusLineAndHeadersFromRaw($statusAndHeadersLines);
        $this->parseStatusLineFromRaw($this->rawSplited['status']);
//        $this->parseHeaderFromRaw($this->rawSplited['headers']);

        $this->shiftBodyFromMessageBuffer();
        $this->parseBodyFromRaw();

//        var_dump($startLineAndHeaders);

        return true;
    }


    public function appendBuffer(string $data)
    {
        $this->buffer .= $data;

    }

    /**
     * HTTP/1.1 200 OK\r\n
     * Api-Version: 1.26\r\n
     * Content-Type: application/json\r\n
     * Docker-Experimental: false\r\n
     * Server: Docker/1.13.1 (linux)\r\n
     * Date: Tue, 23 Apr 2019 07:03:19 GMT\r\n
     * Transfer-Encoding: chunked\r\n
     * \r\n
     * @return bool|string|null
     */
    private function shiftHeadersFromMessageBuffer()
    {
        $this->buffer = ltrim($this->buffer, "\r\n");
        if ($headersSize = strpos($this->buffer, "\r\n\r\n")) {
            $headers = substr($this->buffer, 0, $headersSize + 2);
            $this->buffer = substr($this->buffer, $headersSize + 4);
        } elseif ($headersSize = strpos($this->buffer, "\n\n")) {
            $headers = substr($this->buffer, 0, $headersSize + 1);
            $this->buffer = substr($this->buffer, $headersSize + 2);
        } else {
            $headersSize = strlen($this->buffer);
            $headers = null;
        }
        if ($this->maxHeaderBytes > 0 && $headersSize > $this->maxHeaderBytes) {
            throw new ParseException("Maximum allowable header size exceeded: {$this->maxHeaderBytes}", 431);
        }
        return $headers;
    }


    public function splitStatusLineAndHeadersFromRaw($statusAndHeadersLines)
    {
        $statusLineEndPost = strpos($statusAndHeadersLines, "\n");
        $statusRaw = substr($statusAndHeadersLines, 0, $statusLineEndPost);
        var_dump($statusLineEndPost);
        $headersRaw = substr($statusAndHeadersLines, $statusLineEndPost + 1);

        $this->rawSplited['status'] = $statusRaw;
        $this->rawSplited['headers'] = $headersRaw;
        return;
    }

    public function parseStatusLineFromRaw($statusLine)
    {


        if (preg_match(self::STATUS_LINE_PATTERN, $statusLine, $matches)) {
            $this->protocol = $matches['protocol'];
            $this->statusCode = (int)$matches['status'];
            $this->statusReason = trim($matches['reason']);
        } else {
            throw new ParseException('Invalid status line', 400);
        }
    }

    private function addToBody(string $data)
    {
        $this->bodyBytesConsumed += strlen($data);
        if ($this->maxBodyBytes > 0 && $this->bodyBytesConsumed > $this->maxBodyBytes) {
            throw new ParseException("Maximum allowable body size exceeded: {$this->maxBodyBytes}", 413);
        }

        $this->body .= $data;
    }


    public function parseBodyFromRaw()
    {
//        if ($this->statusCode == 204
//            || $this->statusCode == 304
//            || $this->statusCode < 200
//        ) {
//            goto complete;
//        } elseif ($this->parseFlowHeaders['TRANSFER-ENCODING']) {
//            $this->state = self::BODY_CHUNKS;
//            goto before_body;
//        } elseif ($this->parseFlowHeaders['CONTENT-LENGTH'] === null) {
//            $this->state = self::BODY_IDENTITY_EOF;
//            goto before_body;
//        } elseif ($this->parseFlowHeaders['CONTENT-LENGTH'] > 0) {
//            $this->remainingBodyBytes = $this->parseFlowHeaders['CONTENT-LENGTH'];
//            $this->state = self::BODY_IDENTITY;
//            goto before_body;
//        }
        $this->bodyChunk();
    }

    protected function shiftBodyFromMessageBuffer()
    {
        $bodyEndPos = strpos($this->buffer, "\n");
        $bodyLines = substr($this->buffer, 0, $bodyEndPos);
    }


    public function bodyChunk()
    {
        if (false === ($lineEndPos = strpos($this->buffer, "\r\n"))) {
            throw new NeedMoreDataException();
        }
        if ($lineEndPos === 0) {
            throw new ParseException("Invalid new line; hexadecimal chunk size expected", 400);
        }


        // get chunk length
        $chunkLenHex = substr($this->buffer, 0, $lineEndPos);
        $chunkLenHex = strtolower(trim(ltrim($chunkLenHex, '0'))) ?: 0;
        $this->chunkLenRemaining = hexdec($chunkLenHex);

        // cut length
        $this->buffer = substr($this->buffer, $lineEndPos + 2);

        $bufferLen = strlen($this->buffer);
        if ($bufferLen === $this->chunkLenRemaining) {
            throw new NeedMoreDataException();
        } elseif ($bufferLen === $this->chunkLenRemaining + 1) {
            throw new NeedMoreDataException();
        } elseif ($bufferLen >= $this->chunkLenRemaining + 2) {
            $chunk = substr($this->buffer, 0, $this->chunkLenRemaining);
            $this->buffer = substr($this->buffer, $this->chunkLenRemaining + 2);
            $this->chunkLenRemaining = null;
//            var_dump($chunk);
        }

        $this->addToBody($this->buffer);
        $this->buffer = '';
        $this->chunkLenRemaining -= $bufferLen;
        throw new NeedMoreDataException();
    }

    protected function dumpShowCRLF(string $str)
    {
        var_dump((str_replace(["\r\n"], ["\\r\\n\r\n"], $str)));
    }
}