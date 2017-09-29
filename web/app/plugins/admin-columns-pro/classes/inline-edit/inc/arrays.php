<?php
class CACIE_Arrays {

	/**
	 * Private constructor to prevent instantiation
	 *
	 * @since 1.0
	 */
	private function __construct() {}

	/**
	 * Indents any object as long as it has a unique id and that of its parent.
	 *
	 * @since 1.0
	 *
	 * @param type $array
	 * @param type $parentId
	 * @param type $parentKey
	 * @param type $selfKey
	 * @param type $childrenKey
	 * @return array Indented Array
	 */
	static public function array_nest( $array, $parentId = 0, $parentKey = 'post_parent', $selfKey = 'ID', $childrenKey = 'children' ) {
		$indent = array();

        // clean counter
        $i = 0;

		foreach ( $array as $v ) {
			if ( $v->$parentKey == $parentId ) {
				$indent[$i] = $v;
				$indent[$i]->$childrenKey = CACIE_Arrays::array_nest( $array, $v->$selfKey, $parentKey, $selfKey, $childrenKey );

                $i++;
			}
		}

		return $indent;
	}

	public static function convert_nesting_to_indentation( $nested_array, $indent_key, $children_key = 'children', $depth = 0 )	{
		$i = 0;

		$indented_array = array();

		foreach ( $nested_array as $index => $value ) {
			if ( isset( $value->{$children_key} ) ) {
				$newpart = CACIE_Arrays::convert_nesting_to_indentation( $value->{$children_key}, $indent_key, $children_key, $depth + 1 );
				unset( $value->{$children_key} );
				$value->{$indent_key} = str_repeat( '-', $depth ) . $value->{$indent_key};
				$indented_array[] = $value;
				$indented_array = array_merge( $indented_array, $newpart );
			}
			else {
				$indented_array[] = $value;
			}
		}

		return $indented_array;
	}
}