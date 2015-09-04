<?php

namespace AppBundle\DQL;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Lexer;

/**
 * "POWER" "(" IntegerPrimary "," IntegerPrimary ")"
 */
class PowerFunction extends FunctionNode {

	public $basePrimary;
	public $exponentPrimary;

	/**
	 * @override
	 */
	public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker) {
		return sprintf(
				"POW(%s,%d)",
				$this->basePrimary->dispatch($sqlWalker),
				$this->exponentPrimary->dispatch($sqlWalker)
				);
	}

	/**
	 * @override
	 */
	public function parse(\Doctrine\ORM\Query\Parser $parser) {
		$parser->match(Lexer::T_IDENTIFIER);
		$parser->match(Lexer::T_OPEN_PARENTHESIS);
		$this->basePrimary = $parser->StringExpression();
		$parser->match(Lexer::T_COMMA);
		$this->exponentPrimary = $parser->ArithmeticPrimary();
		$parser->match(Lexer::T_CLOSE_PARENTHESIS);
	}

}