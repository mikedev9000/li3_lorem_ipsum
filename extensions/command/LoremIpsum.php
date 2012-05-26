<?php 

namespace li3_lorem_ipsum\extensions\command;


use lithium\util\String;

class LoremIpsum extends \lithium\console\Command
{    
    public static $models = array();
    
    protected $waitingModels = array();
    
    public function run()
    {
        $models = $this->models;
        
        while( !empty( $models ) )
        {
            foreach( $models as $model => $config )
            {
                $result = $this->generate( $model, $config );
                
                if( $result === true )
                    unset( $models[$model] );
            }
        }
    }
    
    /**
     * Creates random data filled entities using the config options.
     * 
     * @param string $model
     * @param array $config
     */
    public function generate( $model, $config )
    {
        $defaults = array(
            'count' => 20,
            'empty' => array(),
        );
        
        $config += $defaults;
        
        $foreignKeys = $this->getForeignKeys( $model );
        
        if( $foreignKeys === false )
            return false;
        
        for( $i = 0; $i < $config['count']; $i++ )
        {
            $entity = $model::create();
            
            $data = $this->randomRowData( $model, $foreignKeys, $config['empty'] );
            
            if( !$entity->save( $data ) )
                throw new \Exception( "Unable to save entity\r\nModel: {$model}\r\nData: " . json_encode( $entity->errors() ) );
        }
        
        return true;
    }
    
    public function randomRowData( $model, $foreignKeys, $empty )
    {
        $data = array();
        
        foreach( $model::schema() as $field => $schema )
        {
            if( $field == $model::key() || in_array( $field, $empty ) )
                continue;
                
            $values = isset( $foreignKeys[$field] ) ? $foreignKeys[$field] : null;
                
            $value = $this->randomFieldData( $schema, $values );
            
            if( $value !== null )
                $data[$field] = $value;
        }        
        
        return $data;
    }
    
    public function randomFieldData( $schema, $values )
    {        
        if( $schema['null'] == true )
            if( rand(0, 100) < rand(0, 100) )
                return null;
                
        if( $values )
            return $values[rand( 0, count( $values ) - 1 )];
                
        switch ( $schema['type'] )
        {
            case 'integer':
                $value = rand( 0, 9999 );
                break;
            case 'float':
                $value = rand( 0, 9999 ) / rand( 1, 9999 );
                break;
            default:
                $value = String::random( rand( 0, 9999 ) );
                break;
        }
        
        return $value;
    }
    
    public function getForeignKeys( $model )
    {
        $foreignKeys = array();
        
        foreach( $model::relations() as $relationship )
        {
            $data = $relationship->data();
            
            if( $data['type'] != 'belongsTo' )
                continue;
                
            $field = key( $data['key'] );
            
            $related_model = $data['to'];
            
            if( $related_model::count() == 0 )
                return false; //we will have to loop back around to this model
                
            $records = $related_model::all();
            
            $ids = array();
            
            $key = $related_model::key();
            
            foreach( $records as $record )
                $ids[] = $record->{$key};
            
            $foreignKeys[$field] = $ids;
        }
        
        return $foreignKeys;
    }
}