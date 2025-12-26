USE controlo_arpiedi;

-- Update clientes table to include clinical and measurement data
ALTER TABLE clientes 
ADD COLUMN diagnostico TEXT,
ADD COLUMN medidas_json JSON,
ADD COLUMN observaciones TEXT;
