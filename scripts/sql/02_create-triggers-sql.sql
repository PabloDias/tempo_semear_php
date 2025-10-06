       current_timestamp       
-------------------------------
 2025-10-06 21:24:16.445966+00
(1 row)

                                                                  ?column?                                                                   
---------------------------------------------------------------------------------------------------------------------------------------------
 CREATE OR REPLACE FUNCTION public.armor(bytea, text[], text[])                                                                             +
  RETURNS text                                                                                                                              +
  LANGUAGE c                                                                                                                                +
  IMMUTABLE PARALLEL SAFE STRICT                                                                                                            +
 AS '$libdir/pgcrypto', $function$pg_armor$function$                                                                                        +
 ;                                                                                                                                          +
 
 CREATE OR REPLACE FUNCTION public.armor(bytea)                                                                                             +
  RETURNS text                                                                                                                              +
  LANGUAGE c                                                                                                                                +
  IMMUTABLE PARALLEL SAFE STRICT                                                                                                            +
 AS '$libdir/pgcrypto', $function$pg_armor$function$                                                                                        +
 ;                                                                                                                                          +
 
 CREATE OR REPLACE FUNCTION public.atualizar_timestamp()                                                                                    +
  RETURNS trigger                                                                                                                           +
  LANGUAGE plpgsql                                                                                                                          +
 AS $function$                                                                                                                              +
 BEGIN                                                                                                                                      +
     NEW.atualizado_em = CURRENT_TIMESTAMP;                                                                                                 +
     RETURN NEW;                                                                                                                            +
 END;                                                                                                                                       +
 $function$                                                                                                                                 +
 ;                                                                                                                                          +
 
 CREATE OR REPLACE FUNCTION public.crypt(text, text)                                                                                        +
  RETURNS text                                                                                                                              +
  LANGUAGE c                                                                                                                                +
  IMMUTABLE PARALLEL SAFE STRICT                                                                                                            +
 AS '$libdir/pgcrypto', $function$pg_crypt$function$                                                                                        +
 ;                                                                                                                                          +
 
 CREATE OR REPLACE FUNCTION public.dearmor(text)                                                                                            +
  RETURNS bytea                                                                                                                             +
  LANGUAGE c                                                                                                                                +
  IMMUTABLE PARALLEL SAFE STRICT                                                                                                            +
 AS '$libdir/pgcrypto', $function$pg_dearmor$function$                                                                                      +
 ;                                                                                                                                          +
 
 CREATE OR REPLACE FUNCTION public.decrypt(bytea, bytea, text)                                                                              +
  RETURNS bytea                                                                                                                             +
  LANGUAGE c                                                                                                                                +
  IMMUTABLE PARALLEL SAFE STRICT                                                                                                            +
 AS '$libdir/pgcrypto', $function$pg_decrypt$function$                                                                                      +
 ;                                                                                                                                          +
 
 CREATE OR REPLACE FUNCTION public.decrypt_iv(bytea, bytea, bytea, text)                                                                    +
  RETURNS bytea                                                                                                                             +
  LANGUAGE c                                                                                                                                +
  IMMUTABLE PARALLEL SAFE STRICT                                                                                                            +
 AS '$libdir/pgcrypto', $function$pg_decrypt_iv$function$                                                                                   +
 ;                                                                                                                                          +
 
 CREATE OR REPLACE FUNCTION public.digest(bytea, text)                                                                                      +
  RETURNS bytea                                                                                                                             +
  LANGUAGE c                                                                                                                                +
  IMMUTABLE PARALLEL SAFE STRICT                                                                                                            +
 AS '$libdir/pgcrypto', $function$pg_digest$function$                                                                                       +
 ;                                                                                                                                          +
 
 CREATE OR REPLACE FUNCTION public.digest(text, text)                                                                                       +
  RETURNS bytea                                                                                                                             +
  LANGUAGE c                                                                                                                                +
  IMMUTABLE PARALLEL SAFE STRICT                                                                                                            +
 AS '$libdir/pgcrypto', $function$pg_digest$function$                                                                                       +
 ;                                                                                                                                          +
 
 CREATE OR REPLACE FUNCTION public.encrypt(bytea, bytea, text)                                                                              +
  RETURNS bytea                                                                                                                             +
  LANGUAGE c                                                                                                                                +
  IMMUTABLE PARALLEL SAFE STRICT                                                                                                            +
 AS '$libdir/pgcrypto', $function$pg_encrypt$function$                                                                                      +
 ;                                                                                                                                          +
 
 CREATE OR REPLACE FUNCTION public.encrypt_iv(bytea, bytea, bytea, text)                                                                    +
  RETURNS bytea                                                                                                                             +
  LANGUAGE c                                                                                                                                +
  IMMUTABLE PARALLEL SAFE STRICT                                                                                                            +
 AS '$libdir/pgcrypto', $function$pg_encrypt_iv$function$                                                                                   +
 ;                                                                                                                                          +
 
 CREATE OR REPLACE FUNCTION public.gen_random_bytes(integer)                                                                                +
  RETURNS bytea                                                                                                                             +
  LANGUAGE c                                                                                                                                +
  PARALLEL SAFE STRICT                                                                                                                      +
 AS '$libdir/pgcrypto', $function$pg_random_bytes$function$                                                                                 +
 ;                                                                                                                                          +
 
 CREATE OR REPLACE FUNCTION public.gen_random_uuid()                                                                                        +
  RETURNS uuid                                                                                                                              +
  LANGUAGE c                                                                                                                                +
  PARALLEL SAFE                                                                                                                             +
 AS '$libdir/pgcrypto', $function$pg_random_uuid$function$                                                                                  +
 ;                                                                                                                                          +
 
 CREATE OR REPLACE FUNCTION public.gen_salt(text)                                                                                           +
  RETURNS text                                                                                                                              +
  LANGUAGE c                                                                                                                                +
  PARALLEL SAFE STRICT                                                                                                                      +
 AS '$libdir/pgcrypto', $function$pg_gen_salt$function$                                                                                     +
 ;                                                                                                                                          +
 
 CREATE OR REPLACE FUNCTION public.gen_salt(text, integer)                                                                                  +
  RETURNS text                                                                                                                              +
  LANGUAGE c                                                                                                                                +
  PARALLEL SAFE STRICT                                                                                                                      +
 AS '$libdir/pgcrypto', $function$pg_gen_salt_rounds$function$                                                                              +
 ;                                                                                                                                          +
 
 CREATE OR REPLACE FUNCTION public.gerar_protocolo()                                                                                        +
  RETURNS trigger                                                                                                                           +
  LANGUAGE plpgsql                                                                                                                          +
 AS $function$                                                                                                                              +
 DECLARE                                                                                                                                    +
     ano_atual TEXT;                                                                                                                        +
     novo_protocolo TEXT;                                                                                                                   +
 BEGIN                                                                                                                                      +
     -- Só gera protocolo quando o status muda de 'rascunho' para 'enviado'                                                                 +
     IF OLD.status = 'rascunho' AND NEW.status = 'enviado' AND NEW.protocolo IS NULL THEN                                                   +
         ano_atual := EXTRACT(YEAR FROM CURRENT_DATE)::TEXT;                                                                                +
         novo_protocolo := 'TS' || ano_atual || '-' || LPAD(nextval('protocolo_seq')::TEXT, 6, '0');                                        +
         NEW.protocolo := novo_protocolo;                                                                                                   +
         NEW.data_envio := CURRENT_TIMESTAMP;                                                                                               +
         NEW.editavel := FALSE; -- Trava edição após envio                                                                                  +
     END IF;                                                                                                                                +
                                                                                                                                            +
     RETURN NEW;                                                                                                                            +
 END;                                                                                                                                       +
 $function$                                                                                                                                 +
 ;                                                                                                                                          +
 
 CREATE OR REPLACE FUNCTION public.hash_senha(senha text)                                                                                   +
  RETURNS text                                                                                                                              +
  LANGUAGE plpgsql                                                                                                                          +
 AS $function$                                                                                                                              +
 BEGIN                                                                                                                                      +
     RETURN crypt(senha, gen_salt('bf', 8));                                                                                                +
 END;                                                                                                                                       +
 $function$                                                                                                                                 +
 ;                                                                                                                                          +
 
 CREATE OR REPLACE FUNCTION public.hmac(text, text, text)                                                                                   +
  RETURNS bytea                                                                                                                             +
  LANGUAGE c                                                                                                                                +
  IMMUTABLE PARALLEL SAFE STRICT                                                                                                            +
 AS '$libdir/pgcrypto', $function$pg_hmac$function$                                                                                         +
 ;                                                                                                                                          +
 
 CREATE OR REPLACE FUNCTION public.hmac(bytea, bytea, text)                                                                                 +
  RETURNS bytea                                                                                                                             +
  LANGUAGE c                                                                                                                                +
  IMMUTABLE PARALLEL SAFE STRICT                                                                                                            +
 AS '$libdir/pgcrypto', $function$pg_hmac$function$                                                                                         +
 ;                                                                                                                                          +
 
 CREATE OR REPLACE FUNCTION public.limpar_sessoes_expiradas()                                                                               +
  RETURNS void                                                                                                                              +
  LANGUAGE plpgsql                                                                                                                          +
 AS $function$                                                                                                                              +
 BEGIN                                                                                                                                      +
     UPDATE sessoes                                                                                                                         +
     SET ativo = FALSE                                                                                                                      +
     WHERE expira_em < CURRENT_TIMESTAMP AND ativo = TRUE;                                                                                  +
                                                                                                                                            +
     -- Remove sessões muito antigas (mais de 30 dias)                                                                                      +
     DELETE FROM sessoes                                                                                                                    +
     WHERE criado_em < CURRENT_TIMESTAMP - INTERVAL '30 days';                                                                              +
 END;                                                                                                                                       +
 $function$                                                                                                                                 +
 ;                                                                                                                                          +
 
 CREATE OR REPLACE FUNCTION public.pgp_armor_headers(text, OUT key text, OUT value text)                                                    +
  RETURNS SETOF record                                                                                                                      +
  LANGUAGE c                                                                                                                                +
  IMMUTABLE PARALLEL SAFE STRICT                                                                                                            +
 AS '$libdir/pgcrypto', $function$pgp_armor_headers$function$                                                                               +
 ;                                                                                                                                          +
 
 CREATE OR REPLACE FUNCTION public.pgp_key_id(bytea)                                                                                        +
  RETURNS text                                                                                                                              +
  LANGUAGE c                                                                                                                                +
  IMMUTABLE PARALLEL SAFE STRICT                                                                                                            +
 AS '$libdir/pgcrypto', $function$pgp_key_id_w$function$                                                                                    +
 ;                                                                                                                                          +
 
 CREATE OR REPLACE FUNCTION public.pgp_pub_decrypt(bytea, bytea)                                                                            +
  RETURNS text                                                                                                                              +
  LANGUAGE c                                                                                                                                +
  IMMUTABLE PARALLEL SAFE STRICT                                                                                                            +
 AS '$libdir/pgcrypto', $function$pgp_pub_decrypt_text$function$                                                                            +
 ;                                                                                                                                          +
 
 CREATE OR REPLACE FUNCTION public.pgp_pub_decrypt(bytea, bytea, text, text)                                                                +
  RETURNS text                                                                                                                              +
  LANGUAGE c                                                                                                                                +
  IMMUTABLE PARALLEL SAFE STRICT                                                                                                            +
 AS '$libdir/pgcrypto', $function$pgp_pub_decrypt_text$function$                                                                            +
 ;                                                                                                                                          +
 
 CREATE OR REPLACE FUNCTION public.pgp_pub_decrypt(bytea, bytea, text)                                                                      +
  RETURNS text                                                                                                                              +
  LANGUAGE c                                                                                                                                +
  IMMUTABLE PARALLEL SAFE STRICT                                                                                                            +
 AS '$libdir/pgcrypto', $function$pgp_pub_decrypt_text$function$                                                                            +
 ;                                                                                                                                          +
 
 CREATE OR REPLACE FUNCTION public.pgp_pub_decrypt_bytea(bytea, bytea, text, text)                                                          +
  RETURNS bytea                                                                                                                             +
  LANGUAGE c                                                                                                                                +
  IMMUTABLE PARALLEL SAFE STRICT                                                                                                            +
 AS '$libdir/pgcrypto', $function$pgp_pub_decrypt_bytea$function$                                                                           +
 ;                                                                                                                                          +
 
 CREATE OR REPLACE FUNCTION public.pgp_pub_decrypt_bytea(bytea, bytea)                                                                      +
  RETURNS bytea                                                                                                                             +
  LANGUAGE c                                                                                                                                +
  IMMUTABLE PARALLEL SAFE STRICT                                                                                                            +
 AS '$libdir/pgcrypto', $function$pgp_pub_decrypt_bytea$function$                                                                           +
 ;                                                                                                                                          +
 
 CREATE OR REPLACE FUNCTION public.pgp_pub_decrypt_bytea(bytea, bytea, text)                                                                +
  RETURNS bytea                                                                                                                             +
  LANGUAGE c                                                                                                                                +
  IMMUTABLE PARALLEL SAFE STRICT                                                                                                            +
 AS '$libdir/pgcrypto', $function$pgp_pub_decrypt_bytea$function$                                                                           +
 ;                                                                                                                                          +
 
 CREATE OR REPLACE FUNCTION public.pgp_pub_encrypt(text, bytea)                                                                             +
  RETURNS bytea                                                                                                                             +
  LANGUAGE c                                                                                                                                +
  PARALLEL SAFE STRICT                                                                                                                      +
 AS '$libdir/pgcrypto', $function$pgp_pub_encrypt_text$function$                                                                            +
 ;                                                                                                                                          +
 
 CREATE OR REPLACE FUNCTION public.pgp_pub_encrypt(text, bytea, text)                                                                       +
  RETURNS bytea                                                                                                                             +
  LANGUAGE c                                                                                                                                +
  PARALLEL SAFE STRICT                                                                                                                      +
 AS '$libdir/pgcrypto', $function$pgp_pub_encrypt_text$function$                                                                            +
 ;                                                                                                                                          +
 
 CREATE OR REPLACE FUNCTION public.pgp_pub_encrypt_bytea(bytea, bytea, text)                                                                +
  RETURNS bytea                                                                                                                             +
  LANGUAGE c                                                                                                                                +
  PARALLEL SAFE STRICT                                                                                                                      +
 AS '$libdir/pgcrypto', $function$pgp_pub_encrypt_bytea$function$                                                                           +
 ;                                                                                                                                          +
 
 CREATE OR REPLACE FUNCTION public.pgp_pub_encrypt_bytea(bytea, bytea)                                                                      +
  RETURNS bytea                                                                                                                             +
  LANGUAGE c                                                                                                                                +
  PARALLEL SAFE STRICT                                                                                                                      +
 AS '$libdir/pgcrypto', $function$pgp_pub_encrypt_bytea$function$                                                                           +
 ;                                                                                                                                          +
 
 CREATE OR REPLACE FUNCTION public.pgp_sym_decrypt(bytea, text)                                                                             +
  RETURNS text                                                                                                                              +
  LANGUAGE c                                                                                                                                +
  IMMUTABLE PARALLEL SAFE STRICT                                                                                                            +
 AS '$libdir/pgcrypto', $function$pgp_sym_decrypt_text$function$                                                                            +
 ;                                                                                                                                          +
 
 CREATE OR REPLACE FUNCTION public.pgp_sym_decrypt(bytea, text, text)                                                                       +
  RETURNS text                                                                                                                              +
  LANGUAGE c                                                                                                                                +
  IMMUTABLE PARALLEL SAFE STRICT                                                                                                            +
 AS '$libdir/pgcrypto', $function$pgp_sym_decrypt_text$function$                                                                            +
 ;                                                                                                                                          +
 
 CREATE OR REPLACE FUNCTION public.pgp_sym_decrypt_bytea(bytea, text)                                                                       +
  RETURNS bytea                                                                                                                             +
  LANGUAGE c                                                                                                                                +
  IMMUTABLE PARALLEL SAFE STRICT                                                                                                            +
 AS '$libdir/pgcrypto', $function$pgp_sym_decrypt_bytea$function$                                                                           +
 ;                                                                                                                                          +
 
 CREATE OR REPLACE FUNCTION public.pgp_sym_decrypt_bytea(bytea, text, text)                                                                 +
  RETURNS bytea                                                                                                                             +
  LANGUAGE c                                                                                                                                +
  IMMUTABLE PARALLEL SAFE STRICT                                                                                                            +
 AS '$libdir/pgcrypto', $function$pgp_sym_decrypt_bytea$function$                                                                           +
 ;                                                                                                                                          +
 
 CREATE OR REPLACE FUNCTION public.pgp_sym_encrypt(text, text, text)                                                                        +
  RETURNS bytea                                                                                                                             +
  LANGUAGE c                                                                                                                                +
  PARALLEL SAFE STRICT                                                                                                                      +
 AS '$libdir/pgcrypto', $function$pgp_sym_encrypt_text$function$                                                                            +
 ;                                                                                                                                          +
 
 CREATE OR REPLACE FUNCTION public.pgp_sym_encrypt(text, text)                                                                              +
  RETURNS bytea                                                                                                                             +
  LANGUAGE c                                                                                                                                +
  PARALLEL SAFE STRICT                                                                                                                      +
 AS '$libdir/pgcrypto', $function$pgp_sym_encrypt_text$function$                                                                            +
 ;                                                                                                                                          +
 
 CREATE OR REPLACE FUNCTION public.pgp_sym_encrypt_bytea(bytea, text, text)                                                                 +
  RETURNS bytea                                                                                                                             +
  LANGUAGE c                                                                                                                                +
  PARALLEL SAFE STRICT                                                                                                                      +
 AS '$libdir/pgcrypto', $function$pgp_sym_encrypt_bytea$function$                                                                           +
 ;                                                                                                                                          +
 
 CREATE OR REPLACE FUNCTION public.pgp_sym_encrypt_bytea(bytea, text)                                                                       +
  RETURNS bytea                                                                                                                             +
  LANGUAGE c                                                                                                                                +
  PARALLEL SAFE STRICT                                                                                                                      +
 AS '$libdir/pgcrypto', $function$pgp_sym_encrypt_bytea$function$                                                                           +
 ;                                                                                                                                          +
 
 CREATE OR REPLACE FUNCTION public.registrar_historico_edicao()                                                                             +
  RETURNS trigger                                                                                                                           +
  LANGUAGE plpgsql                                                                                                                          +
 AS $function$                                                                                                                              +
 DECLARE                                                                                                                                    +
     campo TEXT;                                                                                                                            +
     valor_antigo TEXT;                                                                                                                     +
     valor_novo TEXT;                                                                                                                       +
 BEGIN                                                                                                                                      +
     -- Só registra se foi alterado por um usuário interno                                                                                  +
     IF NEW.atualizado_por IS NOT NULL THEN                                                                                                 +
         -- Verifica cada campo relevante                                                                                                   +
         IF OLD.nome IS DISTINCT FROM NEW.nome THEN                                                                                         +
             INSERT INTO historico_edicoes (cadastro_id, usuario_id, campo_alterado, valor_anterior, valor_novo)                            +
             VALUES (NEW.id, NEW.atualizado_por, 'nome', OLD.nome, NEW.nome);                                                               +
         END IF;                                                                                                                            +
                                                                                                                                            +
         IF OLD.nome_social IS DISTINCT FROM NEW.nome_social THEN                                                                           +
             INSERT INTO historico_edicoes (cadastro_id, usuario_id, campo_alterado, valor_anterior, valor_novo)                            +
             VALUES (NEW.id, NEW.atualizado_por, 'nome_social', OLD.nome_social, NEW.nome_social);                                          +
         END IF;                                                                                                                            +
                                                                                                                                            +
         IF OLD.cpf IS DISTINCT FROM NEW.cpf THEN                                                                                           +
             INSERT INTO historico_edicoes (cadastro_id, usuario_id, campo_alterado, valor_anterior, valor_novo)                            +
             VALUES (NEW.id, NEW.atualizado_por, 'cpf', OLD.cpf, NEW.cpf);                                                                  +
         END IF;                                                                                                                            +
                                                                                                                                            +
         IF OLD.data_nascimento IS DISTINCT FROM NEW.data_nascimento THEN                                                                   +
             INSERT INTO historico_edicoes (cadastro_id, usuario_id, campo_alterado, valor_anterior, valor_novo)                            +
             VALUES (NEW.id, NEW.atualizado_por, 'data_nascimento', OLD.data_nascimento::TEXT, NEW.data_nascimento::TEXT);                  +
         END IF;                                                                                                                            +
                                                                                                                                            +
         IF OLD.status IS DISTINCT FROM NEW.status THEN                                                                                     +
             INSERT INTO historico_edicoes (cadastro_id, usuario_id, campo_alterado, valor_anterior, valor_novo)                            +
             VALUES (NEW.id, NEW.atualizado_por, 'status', OLD.status::TEXT, NEW.status::TEXT);                                             +
         END IF;                                                                                                                            +
                                                                                                                                            +
         IF OLD.municipio_id IS DISTINCT FROM NEW.municipio_id THEN                                                                         +
             INSERT INTO historico_edicoes (cadastro_id, usuario_id, campo_alterado, valor_anterior, valor_novo)                            +
             VALUES (NEW.id, NEW.atualizado_por, 'municipio_id', OLD.municipio_id::TEXT, NEW.municipio_id::TEXT);                           +
         END IF;                                                                                                                            +
                                                                                                                                            +
         -- Adicionar outros campos conforme necessário                                                                                     +
     END IF;                                                                                                                                +
                                                                                                                                            +
     RETURN NEW;                                                                                                                            +
 END;                                                                                                                                       +
 $function$                                                                                                                                 +
 ;                                                                                                                                          +
 
 CREATE OR REPLACE FUNCTION public.registrar_log_acao()                                                                                     +
  RETURNS trigger                                                                                                                           +
  LANGUAGE plpgsql                                                                                                                          +
 AS $function$                                                                                                                              +
 DECLARE                                                                                                                                    +
     acao_log TEXT;                                                                                                                         +
     nivel_log TEXT;                                                                                                                        +
     tabela_nome TEXT;                                                                                                                      +
 BEGIN                                                                                                                                      +
     tabela_nome := TG_TABLE_NAME;                                                                                                          +
                                                                                                                                            +
     IF TG_OP = 'INSERT' THEN                                                                                                               +
         acao_log := 'Novo registro criado';                                                                                                +
         nivel_log := 'INFO';                                                                                                               +
     ELSIF TG_OP = 'UPDATE' THEN                                                                                                            +
         acao_log := 'Registro atualizado';                                                                                                 +
         nivel_log := 'INFO';                                                                                                               +
                                                                                                                                            +
         -- Log especial APENAS para tabela cadastros                                                                                       +
         IF tabela_nome = 'cadastros' THEN                                                                                                  +
             IF OLD.status IS DISTINCT FROM NEW.status THEN                                                                                 +
                 acao_log := format('Status alterado de %s para %s', OLD.status, NEW.status);                                               +
                 nivel_log := 'WARNING';                                                                                                    +
             END IF;                                                                                                                        +
         END IF;                                                                                                                            +
     ELSIF TG_OP = 'DELETE' THEN                                                                                                            +
         acao_log := 'Registro removido';                                                                                                   +
         nivel_log := 'WARNING';                                                                                                            +
     END IF;                                                                                                                                +
                                                                                                                                            +
     -- Insere o log                                                                                                                        +
     INSERT INTO logs_sistema (nivel, modulo, acao, descricao, dados_adicionais)                                                            +
     VALUES (                                                                                                                               +
         nivel_log,                                                                                                                         +
         tabela_nome,                                                                                                                       +
         acao_log,                                                                                                                          +
         format('Operação %s na tabela %s', TG_OP, tabela_nome),                                                                            +
         jsonb_build_object(                                                                                                                +
             'operacao', TG_OP,                                                                                                             +
             'tabela', tabela_nome,                                                                                                         +
             'registro_id', COALESCE(NEW.id, OLD.id)                                                                                        +
         )                                                                                                                                  +
     );                                                                                                                                     +
                                                                                                                                            +
     IF TG_OP = 'DELETE' THEN                                                                                                               +
         RETURN OLD;                                                                                                                        +
     ELSE                                                                                                                                   +
         RETURN NEW;                                                                                                                        +
     END IF;                                                                                                                                +
 END;                                                                                                                                       +
 $function$                                                                                                                                 +
 ;                                                                                                                                          +
 
 CREATE OR REPLACE FUNCTION public.validar_cpf_digitos(cpf character varying)                                                               +
  RETURNS boolean                                                                                                                           +
  LANGUAGE plpgsql                                                                                                                          +
 AS $function$                                                                                                                              +
 DECLARE                                                                                                                                    +
     soma INTEGER := 0;                                                                                                                     +
     digito1 INTEGER;                                                                                                                       +
     digito2 INTEGER;                                                                                                                       +
     i INTEGER;                                                                                                                             +
 BEGIN                                                                                                                                      +
     -- Remove caracteres não numéricos                                                                                                     +
     cpf := regexp_replace(cpf, '[^0-9]', '', 'g');                                                                                         +
                                                                                                                                            +
     -- Verifica se tem 11 dígitos                                                                                                          +
     IF length(cpf) != 11 THEN                                                                                                              +
         RETURN FALSE;                                                                                                                      +
     END IF;                                                                                                                                +
                                                                                                                                            +
     -- Verifica se todos os dígitos são iguais                                                                                             +
     IF cpf ~ '^(.)\1{10}$' THEN                                                                                                            +
         RETURN FALSE;                                                                                                                      +
     END IF;                                                                                                                                +
                                                                                                                                            +
     -- Calcula primeiro dígito verificador                                                                                                 +
     FOR i IN 1..9 LOOP                                                                                                                     +
         soma := soma + (substring(cpf, i, 1)::INTEGER * (11 - i));                                                                         +
     END LOOP;                                                                                                                              +
                                                                                                                                            +
     digito1 := 11 - (soma % 11);                                                                                                           +
     IF digito1 >= 10 THEN                                                                                                                  +
         digito1 := 0;                                                                                                                      +
     END IF;                                                                                                                                +
                                                                                                                                            +
     -- Verifica primeiro dígito                                                                                                            +
     IF substring(cpf, 10, 1)::INTEGER != digito1 THEN                                                                                      +
         RETURN FALSE;                                                                                                                      +
     END IF;                                                                                                                                +
                                                                                                                                            +
     -- Calcula segundo dígito verificador                                                                                                  +
     soma := 0;                                                                                                                             +
     FOR i IN 1..10 LOOP                                                                                                                    +
         soma := soma + (substring(cpf, i, 1)::INTEGER * (12 - i));                                                                         +
     END LOOP;                                                                                                                              +
                                                                                                                                            +
     digito2 := 11 - (soma % 11);                                                                                                           +
     IF digito2 >= 10 THEN                                                                                                                  +
         digito2 := 0;                                                                                                                      +
     END IF;                                                                                                                                +
                                                                                                                                            +
     -- Verifica segundo dígito                                                                                                             +
     IF substring(cpf, 11, 1)::INTEGER != digito2 THEN                                                                                      +
         RETURN FALSE;                                                                                                                      +
     END IF;                                                                                                                                +
                                                                                                                                            +
     RETURN TRUE;                                                                                                                           +
 END;                                                                                                                                       +
 $function$                                                                                                                                 +
 ;                                                                                                                                          +
 
 CREATE OR REPLACE FUNCTION public.validar_cpf_unico()                                                                                      +
  RETURNS trigger                                                                                                                           +
  LANGUAGE plpgsql                                                                                                                          +
 AS $function$                                                                                                                              +
 BEGIN                                                                                                                                      +
     -- Verifica se já existe outro cadastro com o mesmo CPF                                                                                +
     IF EXISTS (                                                                                                                            +
         SELECT 1 FROM cadastros                                                                                                            +
         WHERE cpf = NEW.cpf                                                                                                                +
         AND id != COALESCE(NEW.id, 0)                                                                                                      +
     ) THEN                                                                                                                                 +
         RAISE EXCEPTION 'CPF % já possui cadastro no sistema', NEW.cpf;                                                                    +
     END IF;                                                                                                                                +
                                                                                                                                            +
     RETURN NEW;                                                                                                                            +
 END;                                                                                                                                       +
 $function$                                                                                                                                 +
 ;                                                                                                                                          +
 
 CREATE OR REPLACE FUNCTION public.validar_edicao_cadastro()                                                                                +
  RETURNS trigger                                                                                                                           +
  LANGUAGE plpgsql                                                                                                                          +
 AS $function$                                                                                                                              +
 DECLARE                                                                                                                                    +
     v_perfil VARCHAR(20);                                                                                                                  +
     v_cadastro_status VARCHAR(20);                                                                                                         +
 BEGIN                                                                                                                                      +
     -- INSERT: Sempre permite                                                                                                              +
     IF TG_OP = 'INSERT' THEN                                                                                                               +
         RETURN NEW;                                                                                                                        +
     END IF;                                                                                                                                +
                                                                                                                                            +
     -- UPDATE: Verifica permissões                                                                                                         +
     IF TG_OP = 'UPDATE' THEN                                                                                                               +
         v_cadastro_status := OLD.status;                                                                                                   +
                                                                                                                                            +
         -- Se tem atualizado_por, é um usuário interno editando                                                                            +
         IF NEW.atualizado_por IS NOT NULL THEN                                                                                             +
             -- Busca o perfil do usuário                                                                                                   +
             SELECT perfil INTO v_perfil                                                                                                    +
             FROM usuarios_internos                                                                                                         +
             WHERE id = NEW.atualizado_por;                                                                                                 +
                                                                                                                                            +
             -- ADMIN: Pode editar TUDO                                                                                                     +
             IF v_perfil = 'admin' THEN                                                                                                     +
                 RETURN NEW;                                                                                                                +
             END IF;                                                                                                                        +
                                                                                                                                            +
             -- SUPERVISOR: Pode editar apenas rascunho e em_analise                                                                        +
             IF v_perfil = 'supervisor' THEN                                                                                                +
                 IF v_cadastro_status IN ('rascunho', 'em_analise') THEN                                                                    +
                     RETURN NEW;  -- ✅ PERMITE                                                                                             +
                 ELSE                                                                                                                       +
                     RAISE EXCEPTION 'Supervisores só podem editar cadastros em rascunho ou em análise. Status atual: %', v_cadastro_status;+
                 END IF;                                                                                                                    +
             END IF;                                                                                                                        +
                                                                                                                                            +
             -- Outros perfis: não permite                                                                                                  +
             RAISE EXCEPTION 'Apenas administradores podem editar cadastros já enviados. Seu perfil é: %', v_perfil;                        +
         END IF;                                                                                                                            +
                                                                                                                                            +
         -- Se não tem atualizado_por, é o beneficiário                                                                                     +
         IF OLD.editavel = FALSE THEN                                                                                                       +
             RAISE EXCEPTION 'Este cadastro não pode mais ser editado.';                                                                    +
         END IF;                                                                                                                            +
                                                                                                                                            +
         RETURN NEW;                                                                                                                        +
     END IF;                                                                                                                                +
                                                                                                                                            +
     RETURN NEW;                                                                                                                            +
 END;                                                                                                                                       +
 $function$                                                                                                                                 +
 ;                                                                                                                                          +
 
 CREATE OR REPLACE FUNCTION public.validar_idade_minima()                                                                                   +
  RETURNS trigger                                                                                                                           +
  LANGUAGE plpgsql                                                                                                                          +
 AS $function$                                                                                                                              +
 DECLARE                                                                                                                                    +
     idade INTEGER;                                                                                                                         +
 BEGIN                                                                                                                                      +
     idade := EXTRACT(YEAR FROM age(NEW.data_nascimento));                                                                                  +
                                                                                                                                            +
     IF idade < 18 THEN                                                                                                                     +
         RAISE EXCEPTION 'Beneficiário deve ter no mínimo 18 anos. Idade calculada: %', idade;                                              +
     END IF;                                                                                                                                +
                                                                                                                                            +
     RETURN NEW;                                                                                                                            +
 END;                                                                                                                                       +
 $function$                                                                                                                                 +
 ;                                                                                                                                          +
 
(46 rows)

                                                                        ?column?                                                                         
---------------------------------------------------------------------------------------------------------------------------------------------------------
 CREATE TRIGGER trg_atualizar_timestamp_arquivos BEFORE UPDATE ON public.arquivos_cadastro FOR EACH ROW EXECUTE FUNCTION atualizar_timestamp();         +
 
 CREATE TRIGGER trg_atualizar_timestamp_beneficiarios BEFORE UPDATE ON public.beneficiarios FOR EACH ROW EXECUTE FUNCTION atualizar_timestamp();        +
 
 CREATE TRIGGER trg_atualizar_timestamp_cadastros BEFORE UPDATE ON public.cadastros FOR EACH ROW EXECUTE FUNCTION atualizar_timestamp();                +
 
 CREATE TRIGGER trg_gerar_protocolo BEFORE UPDATE ON public.cadastros FOR EACH ROW EXECUTE FUNCTION gerar_protocolo();                                  +
 
 CREATE TRIGGER trg_log_cadastros AFTER INSERT OR DELETE OR UPDATE ON public.cadastros FOR EACH ROW EXECUTE FUNCTION registrar_log_acao();              +
 
 CREATE TRIGGER trg_registrar_historico AFTER UPDATE ON public.cadastros FOR EACH ROW EXECUTE FUNCTION registrar_historico_edicao();                    +
 
 CREATE TRIGGER trg_validar_cpf_unico BEFORE INSERT OR UPDATE ON public.cadastros FOR EACH ROW EXECUTE FUNCTION validar_cpf_unico();                    +
 
 CREATE TRIGGER trg_validar_edicao BEFORE UPDATE ON public.cadastros FOR EACH ROW EXECUTE FUNCTION validar_edicao_cadastro();                           +
 
 CREATE TRIGGER trg_validar_idade BEFORE INSERT OR UPDATE ON public.cadastros FOR EACH ROW EXECUTE FUNCTION validar_idade_minima();                     +
 
 CREATE TRIGGER trg_atualizar_timestamp_config BEFORE UPDATE ON public.configuracoes_sistema FOR EACH ROW EXECUTE FUNCTION atualizar_timestamp();       +
 
 CREATE TRIGGER trg_atualizar_timestamp_municipios BEFORE UPDATE ON public.municipios_permitidos FOR EACH ROW EXECUTE FUNCTION atualizar_timestamp();   +
 
 CREATE TRIGGER trg_atualizar_timestamp_usuarios_internos BEFORE UPDATE ON public.usuarios_internos FOR EACH ROW EXECUTE FUNCTION atualizar_timestamp();+
 
(12 rows)

