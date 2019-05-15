<?php


namespace App\Services;


class NormalizedStringService
{
    /**
     * @param $string
     * @return bool
     * @throws \Exception
     */
    public function isStringNormalized($string)
    {
        if (!$this->isValidString($string)) {
            throw new \Exception('String contains invalid symbols');
        }

        $res = $this->getInsideString($string);
        if (mb_strlen($res) == 0) {
            return true;
        }

        return false;
    }

    /**
     * @param $fullString
     * @return string
     * @throws \Exception
     */
    private function getInsideString($fullString)
    {
        if (mb_strlen($fullString) <= 1) {
            return $fullString;
        }

        $firstSymbol = $fullString[0];
        $lastSymbol = $fullString[mb_strlen($fullString)-1];
        $nextSymbol = $fullString[1];

        $oppositeOfFirstSymbol = $this->getOppositeString($firstSymbol);

        if ($oppositeOfFirstSymbol == $lastSymbol) {
            $cutString = substr($fullString,1,mb_strlen($fullString)-2);
            return $this->getInsideString($cutString);
        }

        if ($oppositeOfFirstSymbol == $nextSymbol) {
            $cutString = substr($fullString,2,mb_strlen($fullString)-2);
            return $this->getInsideString($cutString);
        }

        return $fullString;
    }

    private function isValidString($string)
    {
        return preg_match('/^[\{\}\(\)\[\]]+$/', $string);
    }

    /**
     * @param $symbol
     * @return mixed
     * @throws \Exception
     */
    private function getOppositeString($symbol)
    {
        $arr = [
            '{' => '}',
            '[' => ']',
            '(' => ')',
        ];

        if (!isset($arr[$symbol])) {
            throw new \Exception('Unknown symbol');
        }

        return $arr[$symbol];
    }
}