<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\widgets;

use Yii;
use yii\base\InvalidConfigException;
use yii\base\Widget;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

/**
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
abstract class BaseListView extends Widget
{
	/**
	 * @var array the HTML attributes for the container tag of the list view.
	 * The "tag" element specifies the tag name of the container element and defaults to "div".
	 */
	public $options = array();
	/**
	 * @var \yii\data\DataProviderInterface the data provider for the view. This property is required.
	 */
	public $dataProvider;
	/**
	 * @var array the configuration for the pager widget. By default, [[LinkPager]] will be
	 * used to render the pager. You can use a different widget class by configuring the "class" element.
	 */
	public $pager = array();
	/**
	 * @var array the configuration for the sorter widget. By default, [[LinkSorter]] will be
	 * used to render the sorter. You can use a different widget class by configuring the "class" element.
	 */
	public $sorter = array();
	/**
	 * @var string the HTML content to be displayed as the summary of the list view.
	 * If you do not want to show the summary, you may set it with an empty string.
	 *
	 * The following tokens will be replaced with the corresponding values:
	 *
	 * - `{begin}`: the starting row number (1-based) currently being displayed
	 * - `{end}`: the ending row number (1-based) currently being displayed
	 * - `{count}`: the number of rows currently being displayed
	 * - `{totalCount}`: the total number of rows available
	 * - `{page}`: the page number (1-based) current being displayed
	 * - `{pageCount}`: the number of pages available
	 */
	public $summary;
	/**
	 * @var string|boolean the HTML content to be displayed when [[dataProvider]] does not have any data.
	 * If false, the list view will still be displayed (without body content though).
	 */
	public $empty;
	/**
	 * @var string the layout that determines how different sections of the list view should be organized.
	 * The following tokens will be replaced with the corresponding section contents:
	 *
	 * - `{summary}`: the summary section. See [[renderSummary()]].
	 * - `{items}`: the list items. See [[renderItems()]].
	 * - `{sorter}`: the sorter. See [[renderSorter()]].
	 * - `{pager}`: the pager. See [[renderPager()]].
	 */
	public $layout = "{summary}\n{items}\n{pager}";


	/**
	 * Renders the data models.
	 * @return string the rendering result.
	 */
	abstract public function renderItems();

	/**
	 * Initializes the view.
	 */
	public function init()
	{
		if ($this->dataProvider === null) {
			throw new InvalidConfigException('The "dataProvider" property must be set.');
		}
		$this->dataProvider->prepare();
	}

	/**
	 * Runs the widget.
	 */
	public function run()
	{
		if ($this->dataProvider->getCount() > 0 || $this->empty === false) {
			$widget = $this;
			$content = preg_replace_callback("/{\\w+}/", function ($matches) use ($widget) {
				$content = $widget->renderSection($matches[0]);
				return $content === false ? $matches[0] : $content;
			}, $this->layout);
		} else {
			$content = '<div class="empty">' . ($this->empty === null ? Yii::t('yii', 'No results found.') : $this->empty) . '</div>';
		}
		$tag = ArrayHelper::remove($this->options, 'tag', 'div');
		echo Html::tag($tag, $content, $this->options);
	}

	/**
	 * Renders a section of the specified name.
	 * If the named section is not supported, false will be returned.
	 * @param string $name the section name, e.g., `{summary}`, `{items}`.
	 * @return string|boolean the rendering result of the section, or false if the named section is not supported.
	 */
	public function renderSection($name)
	{
		switch ($name) {
			case '{summary}':
				return $this->renderSummary();
			case '{items}':
				return $this->renderItems();
			case '{pager}':
				return $this->renderPager();
			case '{sorter}':
				return $this->renderSorter();
			default:
				return false;
		}
	}

	/**
	 * Renders the summary text.
	 */
	public function renderSummary()
	{
		$count = $this->dataProvider->getCount();
		if (($pagination = $this->dataProvider->getPagination()) !== false && $count > 0) {
			$totalCount = $this->dataProvider->getTotalCount();
			$begin = $pagination->getPage() * $pagination->pageSize + 1;
			$end = $begin + $count - 1;
			$page = $pagination->getPage() + 1;
			$pageCount = $pagination->pageCount;
			if (($summaryContent = $this->summary) === null) {
				$summaryContent = '<div class="summary">' . Yii::t('yii', 'Showing <b>{begin}-{end}</b> of <b>{totalCount}</b> {0, plural, =1{item} other{items}}.', $totalCount) . '</div>';
			}
		} else {
			$begin = $page = $pageCount = 1;
			$end = $totalCount = $count;
			if (($summaryContent = $this->summary) === null) {
				$summaryContent = '<div class="summary">' . Yii::t('yii', 'Total <b>{count}</b> {0, plural, =1{item} other{items}}.', $count) . '</div>';
			}
		}
		return strtr($summaryContent, array(
			'{begin}' => $begin,
			'{end}' => $end,
			'{count}' => $count,
			'{totalCount}' => $totalCount,
			'{page}' => $page,
			'{pageCount}' => $pageCount,
		));
	}

	/**
	 * Renders the pager.
	 * @return string the rendering result
	 */
	public function renderPager()
	{
		$pagination = $this->dataProvider->getPagination();
		if ($pagination === false || $this->dataProvider->getCount() <= 0) {
			return '';
		}
		/** @var LinkPager $class */
		$class = ArrayHelper::remove($this->pager, 'class', LinkPager::className());
		$this->pager['pagination'] = $pagination;
		return $class::widget($this->pager);
	}

	/**
	 * Renders the sorter.
	 * @return string the rendering result
	 */
	public function renderSorter()
	{
		$sort = $this->dataProvider->getSort();
		if ($sort === false || empty($sort->attributes) || $this->dataProvider->getCount() <= 0) {
			return '';
		}
		/** @var LinkSorter $class */
		$class = ArrayHelper::remove($this->sorter, 'class', LinkSorter::className());
		$this->sorter['sort'] = $sort;
		return $class::widget($this->sorter);
	}
}
