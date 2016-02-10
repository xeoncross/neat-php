<?php


Class Table
{
	// Array of data rows
	public $rows;

	// List of all table columns
	public $columns;

	// Ordering parameters
	public $column;
	public $sort;
	public $url;

	// Existing parameters
	public $params;

	// Table ID
	public $id;

	/**
	 * Create the table object using these rows
	 *
	 * @param array $rows to use
	 */
	public function __construct($rows)
	{
		$this->rows = $rows;

		// Set order defaults
		$this->url = current_url();
		$this->params = $_GET;
		$this->column = get('column');
		$this->sort = get('sort', 'asc');
	}


	/**
	 * Add a new field to the validation object
	 *
	 * @param string $field name
	 */
	public function column($header, $name, $function = NULL)
	{
		$this->columns[$header] = array($name, $function);

		return $this;
	}

	public function render()
	{
		$html = '<table'. ($this->id ? ' id="' . $this->id . '"' : '') . ">\n\t<thead>\n\t\t<tr>";

		foreach($this->columns as $header => $data)
		{
			$html .= "\n\t\t\t<th>";

			// If we allow sorting by this column
			if($data[0])
			{
				// If this column matches the current sort column - go in reverse
				if($this->column === $data[0])
				{
					$sort = $this->sort == 'asc' ? 'desc' : 'asc';
				}
				else
				{
					$sort = $this->sort == 'asc' ? 'asc' : 'desc';
				}

				// Build URL parameters taking existing parameters into account
				$params = http_build_query(array_merge($this->params, array(
					'column' => $data[0],
					'sort' => $sort,
				)));

				$html .= '<a href="' . $this->url . '?' . $params . '" class="table_sort_' . $sort . '">' . $header . '</a>';
			}
			else
			{
				$html .= $header;
			}

			$html .= "</th>";
		}

		$html .= "\n\t\t</tr>\n\t</thead>\n\t<tbody>";

		foreach($this->rows as $row)
		{
			$html .= "\n\t\t<tr>";
			foreach($this->columns as $header => $data)
			{
				if($data[1])
				{
					$html .= "\n\t\t\t<td>" . $data[1]($row) . "</td>";
				}
				else
				{
					$html .= "\n\t\t\t<td>" . $row->$data[0] . "</td>";
				}
			}
			$html .= "\n\t\t</tr>";
		}

		return $html . "\n\t<tbody>\n</table>";
	}
}
