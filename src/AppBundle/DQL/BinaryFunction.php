<?php

namespace AppBundle\DQL;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Lexer;

/**
 * "BINARY" "(" StringPrimary ")"
 */
class BinaryFunction extends FunctionNode {

    public $stringPrimary;

    /**
     * @override
     */
    public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker) {
		return 'binary ' . $this->stringPrimary->dispatch($sqlWalker);
    }

    /**
     * @override
     */
    public function parse(\Doctrine\ORM\Query\Parser $parser) {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
        $this->stringPrimary = $parser->StringPrimary();
        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

}