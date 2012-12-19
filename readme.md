#CMultiActiveDataProvider

This class allows you to set up a data provider in Yii for use with CListViewWidget that can retrieve and
paginate multiple unrelated AR models.

To use, drop the file in the protected/components folder and follow the example below:

    $dataProvider = new CMultiActiveDataProvider(
        array(
            array('class' => 'FirstModel', 'sortColumn' => 'column_to_sort_first_model_by',
                'pk' => 'first_model_primary_key_column'),
            array('class' => 'SecondModel', 'sortColumn' => 'column_to_sort_second_column_by',
                'pk' => 'second_model_primary_key_column'),
        ),
        array('pagination' => array(
                'pageSize' => 5
            )),
        'ASC' //sort order ASC or DESC (defaults to DESC)
    );