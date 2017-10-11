/**
 * get a pager html render
 *
 * @param Array $p
 * array(
 * 		'base'  => 'base url, like: product/list',
 * 		'cnt' => 'total items count',
 * 		'cur'   => 'current page id',
 * 		'size' => 'Optional, item count per page',
 * 		'span' => 'Optional, gap count between pager button',
 * )
 *
 * @return Array {
 * 		'start'=>'the start offset in queryLimit',
 * 		'rows'=>'rows to fetch in queryLimit',
 * 		'html'=>'page html render, e.g. 1  3 4 5 6  8'
 * }
 */
function pager(array $p)
{
	//==parse page variables
	if (empty($p['size'])) $p['size'] = PAGE_SIZE;
	if (empty($p['span'])) $p['span'] = PAGE_SPAN;

	//==if $p['base'] is not trailing with / or = (like user/list/ or user/list/?p=), 
	//add / to the end of base. eg. p[base] = user/list to user/list/. 
	$pBaseLastChar = substr($p['base'], -1);
	if ($pBaseLastChar != '/' && $pBaseLastChar != '=') $p['base'] .= '/';

	if ($p['cnt'] <= 0) {
		return array('start'=>0, 'rows'=>0, 'html'=>'');
	}

	if (($p['cnt'] % $p['size']) == 0) {
		$p['total'] = $p['cnt'] / $p['size'];
	} else {
		$p['total'] = floor($p['cnt'] / $p['size']) + 1;
	}
	//if only have one page don't show the pager
	if ($p['total'] == 1) return array('start'=>0, 'rows'=>0, 'html'=>'');

	if (isset($p['cur'])) {
		$p['cur'] = intval($p['cur']);
	} else {
		$p['cur'] = 1;
	}
	if ($p['cur'] < 1) {
		$p['cur'] = 1;
	}
	if ($p['cur'] > $p['total']) {
		$p['cur'] = $p['total'];
	}

	if ($p['total'] <= $p['span']+1) {
		$p['start'] = 1;
		$p['end'] = $p['total'];
	} else {
		if ($p['cur'] < $p['span']+1) {
			$p['start'] = 1;
			$p['end'] = $p['start'] + $p['span'];
		} else {
			$p['start'] = $p['cur'] - $p['span'] + 1;
			if ($p['start'] > $p['total']-$p['span']) $p['start'] = $p['total'] - $p['span'];
			$p['end'] = $p['start'] + $p['span'];
		}
	}
	if ($p['start'] < 1) $p['start'] = 1;
	if ($p['end'] > $p['total']) $p['end'] = $p['total'];


	$p['offset'] = ($p['cur'] - 1) * $p['size'];


	//==render with html
	$html = '';
	if ($p['start'] != 1) {
		$html .='<a href="'. url($p['base'].'1') .'" class="p">1</a>';
		if ($p['start'] - 1 > 1) $html .='&bull;&bull;';
	}
	for ($i = $p['start']; $i <= $p['end']; $i++) {
		if ($p['cur'] == $i) {
			$html .='<strong class="p_cur">' . $i . '</strong>';
		} else {
			$html .='<a href="'. url($p['base'].$i) .'" class="p">' . $i . '</a>';
		}
	}
	if ($p['end'] != $p['total']) {
		if ($p['total'] - $p['end'] > 1) $html .='&bull;&bull;';
		$html .= '<a href="'. url($p['base'].$p['total']) .'" class="p">' . $p['total'] . '</a>';
	}
	$html .= '<strong class="p_info">' . $p['cnt'] . '&nbsp'.'total items | ' . $p['size'] .'&nbsp'.'items each page</strong>';

	return array('start'=>$p['offset'], 'rows'=>$p['size'], 'html'=>$html, '');
}
