<?php

namespace AppBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query;

use Gedmo\Translatable\TranslatableListener;

/**
 * Class TranslatableRepository
 *
 * This is my translatable repository that offers methods to retrieve results with translations
 */
class TranslatableRepository extends EntityRepository
{
	/**
	 * @var string Default locale
	 */
	protected $defaultLocale;

	/**
	 * Sets default locale
	 *
	 * @param string $locale
	 */
	public function setDefaultLocale($locale)
	{
		$this->defaultLocale = $locale;
		return $this;
	}

	/**
	 * Returns translated one (or null if not found) result for given locale
	 *
	 * @param QueryBuilder $qb            A Doctrine query builder instance
	 * @param string       $locale        A locale name
	 * @param string       $hydrationMode A Doctrine results hydration mode
	 *
	 * @return QueryBuilder
	 */
	public function getOneOrNullResult(QueryBuilder $qb, $locale = null, $hydrationMode = null)
	{
		return $this->getTranslatedQuery($qb, $locale)->getOneOrNullResult($hydrationMode);
	}

	/**
	 * Returns translated results for given locale
	 *
	 * @param QueryBuilder $qb            A Doctrine query builder instance
	 * @param string       $locale        A locale name
	 * @param string       $hydrationMode A Doctrine results hydration mode
	 *
	 * @return QueryBuilder
	 */
	public function getResult(QueryBuilder $qb, $locale = null, $hydrationMode = AbstractQuery::HYDRATE_OBJECT)
	{
		return $this->getTranslatedQuery($qb, $locale)->getResult($hydrationMode);
	}

	/**
	 * Returns translated array results for given locale
	 *
	 * @param QueryBuilder $qb     A Doctrine query builder instance
	 * @param string       $locale A locale name
	 *
	 * @return QueryBuilder
	 */
	public function getArrayResult(QueryBuilder $qb, $locale = null)
	{
		return $this->getTranslatedQuery($qb, $locale)->getArrayResult();
	}

	/**
	 * Returns translated single result for given locale
	 *
	 * @param QueryBuilder $qb            A Doctrine query builder instance
	 * @param string       $locale        A locale name
	 * @param string       $hydrationMode A Doctrine results hydration mode
	 *
	 * @return QueryBuilder
	 */
	public function getSingleResult(QueryBuilder $qb, $locale = null, $hydrationMode = null)
	{
		return $this->getTranslatedQuery($qb, $locale)->getSingleResult($hydrationMode);
	}

	/**
	 * Returns translated scalar result for given locale
	 *
	 * @param QueryBuilder $qb     A Doctrine query builder instance
	 * @param string       $locale A locale name
	 *
	 * @return QueryBuilder
	 */
	public function getScalarResult(QueryBuilder $qb, $locale = null)
	{
		return $this->getTranslatedQuery($qb, $locale)->getScalarResult();
	}

	/**
	 * Returns translated single scalar result for given locale
	 *
	 * @param QueryBuilder $qb     A Doctrine query builder instance
	 * @param string       $locale A locale name
	 *
	 * @return QueryBuilder
	 */
	public function getSingleScalarResult(QueryBuilder $qb, $locale = null)
	{
		return $this->getTranslatedQuery($qb, $locale)->getSingleScalarResult();
	}

	/**
	 * Returns translated Doctrine query instance
	 *
	 * @param QueryBuilder $qb     A Doctrine query builder instance
	 * @param string       $locale A locale name
	 *
	 * @return Query
	 */
	protected function getTranslatedQuery(QueryBuilder $qb, $locale = null)
	{
		$locale = null === $locale ? $this->defaultLocale : $locale;

		$query = $qb->getQuery();

		$query->setHint(
				Query::HINT_CUSTOM_OUTPUT_WALKER,
				'Gedmo\\Translatable\\Query\\TreeWalker\\TranslationWalker'
		);

		$query->setHint(TranslatableListener::HINT_TRANSLATABLE_LOCALE, $locale);

		$query->setHint(
			TranslatableListener::HINT_FALLBACK, 
			1
		);

		return $query;
	}
}