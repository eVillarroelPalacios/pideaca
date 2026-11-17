--
-- PostgreSQL database dump
--

\restrict 0pRbdfLKhuns0eYExvIQNV1EQT4oGOTlcxnerdhmtTCYGLWc3P03X0NQyhuwm3B

-- Dumped from database version 16.15
-- Dumped by pg_dump version 16.15

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: addresses; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.addresses (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    country_id bigint NOT NULL,
    province_id bigint,
    department_id bigint,
    region_id bigint,
    postal_code character varying(20),
    street character varying(255),
    number character varying(30),
    floor_apartment character varying(40),
    notes text,
    latitude numeric(10,7),
    longitude numeric(10,7),
    is_primary boolean DEFAULT false NOT NULL,
    sort_order integer DEFAULT 0 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: addresses_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.addresses_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: addresses_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.addresses_id_seq OWNED BY public.addresses.id;


--
-- Name: cache; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cache (
    key character varying(255) NOT NULL,
    value text NOT NULL,
    expiration integer NOT NULL
);


--
-- Name: cache_locks; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cache_locks (
    key character varying(255) NOT NULL,
    owner character varying(255) NOT NULL,
    expiration integer NOT NULL
);


--
-- Name: categories; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.categories (
    id bigint NOT NULL,
    provider_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    sort_order integer DEFAULT 0 NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: categories_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.categories_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: categories_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.categories_id_seq OWNED BY public.categories.id;


--
-- Name: contact_inquiries; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.contact_inquiries (
    id bigint NOT NULL,
    full_name character varying(255) NOT NULL,
    email character varying(255) NOT NULL,
    inquiry_type character varying(20) NOT NULL,
    message text NOT NULL,
    status character varying(20) DEFAULT 'new'::character varying NOT NULL,
    created_at timestamp(0) without time zone
);


--
-- Name: contact_inquiries_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.contact_inquiries_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: contact_inquiries_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.contact_inquiries_id_seq OWNED BY public.contact_inquiries.id;


--
-- Name: countries; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.countries (
    id bigint NOT NULL,
    iso_code character varying(2) NOT NULL,
    name character varying(255) NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: countries_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.countries_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: countries_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.countries_id_seq OWNED BY public.countries.id;


--
-- Name: currencies; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.currencies (
    id bigint NOT NULL,
    country_id bigint NOT NULL,
    code character varying(3) NOT NULL,
    name character varying(255) NOT NULL,
    symbol character varying(8) NOT NULL,
    symbol_position character varying(8) DEFAULT 'before'::character varying NOT NULL,
    symbol_space boolean DEFAULT true NOT NULL,
    decimals smallint DEFAULT '2'::smallint NOT NULL,
    decimal_separator character(1) DEFAULT ','::bpchar NOT NULL,
    thousand_separator character(1) DEFAULT '.'::bpchar NOT NULL,
    locale character varying(12) DEFAULT 'es-AR'::character varying NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: currencies_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.currencies_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: currencies_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.currencies_id_seq OWNED BY public.currencies.id;


--
-- Name: customer_subscription_items; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.customer_subscription_items (
    id bigint NOT NULL,
    customer_subscription_id bigint NOT NULL,
    product_id bigint NOT NULL,
    quantity integer DEFAULT 1 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: customer_subscription_items_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.customer_subscription_items_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: customer_subscription_items_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.customer_subscription_items_id_seq OWNED BY public.customer_subscription_items.id;


--
-- Name: customer_subscriptions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.customer_subscriptions (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    provider_id bigint NOT NULL,
    subscription_plan_id bigint NOT NULL,
    status character varying(255) NOT NULL,
    next_delivery_date timestamp(0) without time zone NOT NULL,
    delivery_address_id bigint NOT NULL,
    payment_method character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    preferred_delivery_day smallint,
    preferred_delivery_time time(0) without time zone,
    CONSTRAINT customer_subscriptions_preferred_delivery_day_check CHECK (((preferred_delivery_day IS NULL) OR ((preferred_delivery_day >= 1) AND (preferred_delivery_day <= 7)))),
    CONSTRAINT customer_subscriptions_status_check CHECK (((status)::text = ANY ((ARRAY['ACTIVE'::character varying, 'PAUSED'::character varying, 'CANCELLED'::character varying, 'PAYMENT_FAILED'::character varying])::text[])))
);


--
-- Name: customer_subscriptions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.customer_subscriptions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: customer_subscriptions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.customer_subscriptions_id_seq OWNED BY public.customer_subscriptions.id;


--
-- Name: driver_wallets; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.driver_wallets (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    balance numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    pending_balance numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: driver_wallets_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.driver_wallets_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: driver_wallets_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.driver_wallets_id_seq OWNED BY public.driver_wallets.id;


--
-- Name: driver_withdrawal_requests; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.driver_withdrawal_requests (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    wallet_transaction_id bigint,
    amount numeric(12,2) NOT NULL,
    status character varying(20) DEFAULT 'pending'::character varying NOT NULL,
    method character varying(30),
    reference character varying(100),
    notes text,
    reviewed_by bigint,
    reviewed_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: driver_withdrawal_requests_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.driver_withdrawal_requests_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: driver_withdrawal_requests_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.driver_withdrawal_requests_id_seq OWNED BY public.driver_withdrawal_requests.id;


--
-- Name: failed_jobs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.failed_jobs (
    id bigint NOT NULL,
    uuid character varying(255) NOT NULL,
    connection text NOT NULL,
    queue text NOT NULL,
    payload text NOT NULL,
    exception text NOT NULL,
    failed_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.failed_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.failed_jobs_id_seq OWNED BY public.failed_jobs.id;


--
-- Name: favorites; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.favorites (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    provider_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: favorites_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.favorites_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: favorites_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.favorites_id_seq OWNED BY public.favorites.id;


--
-- Name: group_statuses; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.group_statuses (
    id bigint NOT NULL,
    description character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: group_statuses_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.group_statuses_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: group_statuses_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.group_statuses_id_seq OWNED BY public.group_statuses.id;


--
-- Name: groups; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.groups (
    id bigint NOT NULL,
    description character varying(255) NOT NULL,
    icon text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    group_status_id bigint,
    module_id bigint
);


--
-- Name: groups_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.groups_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: groups_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.groups_id_seq OWNED BY public.groups.id;


--
-- Name: inventory_movements; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.inventory_movements (
    id bigint NOT NULL,
    product_id bigint NOT NULL,
    provider_id bigint NOT NULL,
    order_id bigint,
    movement_type character varying(255) NOT NULL,
    quantity numeric(10,3) NOT NULL,
    unit_of_measure_id bigint NOT NULL,
    unit_conversion_factor numeric(10,4) NOT NULL,
    quantity_in_base_unit numeric(10,3) NOT NULL,
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT inventory_movements_movement_type_check CHECK (((movement_type)::text = ANY ((ARRAY['IN'::character varying, 'OUT'::character varying, 'ADJUSTMENT'::character varying, 'SALE'::character varying, 'CANCELLED_SALE'::character varying])::text[])))
);


--
-- Name: inventory_movements_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.inventory_movements_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: inventory_movements_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.inventory_movements_id_seq OWNED BY public.inventory_movements.id;


--
-- Name: job_batches; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.job_batches (
    id character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    total_jobs integer NOT NULL,
    pending_jobs integer NOT NULL,
    failed_jobs integer NOT NULL,
    failed_job_ids text NOT NULL,
    options text,
    cancelled_at integer,
    created_at integer NOT NULL,
    finished_at integer
);


--
-- Name: jobs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.jobs (
    id bigint NOT NULL,
    queue character varying(255) NOT NULL,
    payload text NOT NULL,
    attempts smallint NOT NULL,
    reserved_at integer,
    available_at integer NOT NULL,
    created_at integer NOT NULL
);


--
-- Name: jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.jobs_id_seq OWNED BY public.jobs.id;


--
-- Name: marketing_campaign_rules; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.marketing_campaign_rules (
    id bigint NOT NULL,
    provider_id bigint NOT NULL,
    rule_type character varying(255) NOT NULL,
    days_inactive integer DEFAULT 15,
    day_of_week integer,
    message_template text NOT NULL,
    discount_code character varying(50),
    is_enabled boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT marketing_campaign_rules_day_of_week_check CHECK (((day_of_week IS NULL) OR ((day_of_week >= 1) AND (day_of_week <= 7)))),
    CONSTRAINT marketing_campaign_rules_days_inactive_check CHECK (((days_inactive IS NULL) OR (days_inactive >= 0))),
    CONSTRAINT marketing_campaign_rules_rule_type_check CHECK (((rule_type)::text = ANY ((ARRAY['INACTIVE_CUSTOMER'::character varying, 'RECURRING_DAY_REMINDER'::character varying, 'WELCOME_BACK'::character varying])::text[])))
);


--
-- Name: marketing_campaign_rules_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.marketing_campaign_rules_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: marketing_campaign_rules_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.marketing_campaign_rules_id_seq OWNED BY public.marketing_campaign_rules.id;


--
-- Name: marketing_logs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.marketing_logs (
    id bigint NOT NULL,
    provider_id bigint NOT NULL,
    user_id bigint NOT NULL,
    campaign_rule_id bigint NOT NULL,
    sent_at timestamp(0) without time zone NOT NULL,
    status character varying(255) NOT NULL,
    CONSTRAINT marketing_logs_status_check CHECK (((status)::text = ANY ((ARRAY['SENT'::character varying, 'FAILED'::character varying, 'CONVERTED'::character varying])::text[])))
);


--
-- Name: marketing_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.marketing_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: marketing_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.marketing_logs_id_seq OWNED BY public.marketing_logs.id;


--
-- Name: migrations; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);


--
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.migrations_id_seq OWNED BY public.migrations.id;


--
-- Name: modules; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.modules (
    id bigint NOT NULL,
    description character varying(255) NOT NULL,
    created_at timestamp without time zone,
    updated_at timestamp without time zone,
    icon text
);


--
-- Name: modules_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.modules_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: modules_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.modules_id_seq OWNED BY public.modules.id;


--
-- Name: order_item_options; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.order_item_options (
    id bigint NOT NULL,
    order_item_id bigint NOT NULL,
    group_name character varying(255) NOT NULL,
    option_name character varying(255) NOT NULL,
    extra_price numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: order_item_options_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.order_item_options_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: order_item_options_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.order_item_options_id_seq OWNED BY public.order_item_options.id;


--
-- Name: order_items; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.order_items (
    id bigint NOT NULL,
    order_id bigint NOT NULL,
    product_id bigint,
    variant_id bigint,
    product_name character varying(255) NOT NULL,
    variant_name character varying(255),
    quantity integer DEFAULT 1 NOT NULL,
    unit_price numeric(10,2) NOT NULL,
    total_price numeric(10,2) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: order_items_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.order_items_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: order_items_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.order_items_id_seq OWNED BY public.order_items.id;


--
-- Name: order_ratings; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.order_ratings (
    id bigint NOT NULL,
    order_id bigint NOT NULL,
    reviewer_id bigint NOT NULL,
    ratee_id bigint NOT NULL,
    stars smallint NOT NULL,
    comment text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: order_ratings_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.order_ratings_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: order_ratings_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.order_ratings_id_seq OWNED BY public.order_ratings.id;


--
-- Name: orders; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.orders (
    id bigint NOT NULL,
    provider_id bigint NOT NULL,
    user_id bigint NOT NULL,
    address_id bigint,
    order_number character varying(255) NOT NULL,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    subtotal numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    delivery_fee numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    discount numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    total numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    payment_method character varying(255) NOT NULL,
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    customer_subscription_id bigint,
    delivery_type character varying(30) DEFAULT 'delivery'::character varying NOT NULL,
    driver_id bigint,
    delivery_status character varying(30) DEFAULT 'pending'::character varying NOT NULL,
    delivery_pin character varying(10),
    released_to_pool_at timestamp(0) without time zone,
    delivery_distance_km numeric(8,2)
);


--
-- Name: orders_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.orders_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: orders_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.orders_id_seq OWNED BY public.orders.id;


--
-- Name: page_type_user; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.page_type_user (
    id bigint NOT NULL,
    type_user_id bigint NOT NULL,
    page_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: page_type_user_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.page_type_user_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: page_type_user_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.page_type_user_id_seq OWNED BY public.page_type_user.id;


--
-- Name: page_user; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.page_user (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    page_id bigint NOT NULL,
    created_at timestamp without time zone,
    updated_at timestamp without time zone
);


--
-- Name: page_user_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.page_user_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: page_user_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.page_user_id_seq OWNED BY public.page_user.id;


--
-- Name: pages; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.pages (
    id bigint NOT NULL,
    description character varying(255) NOT NULL,
    module_id bigint NOT NULL,
    created_at timestamp without time zone,
    updated_at timestamp without time zone,
    url character varying(500)
);


--
-- Name: pages_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.pages_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: pages_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.pages_id_seq OWNED BY public.pages.id;


--
-- Name: password_reset_requests; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.password_reset_requests (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    token character varying(64) NOT NULL,
    expires_at timestamp(0) without time zone NOT NULL,
    used_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: password_reset_requests_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.password_reset_requests_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: password_reset_requests_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.password_reset_requests_id_seq OWNED BY public.password_reset_requests.id;


--
-- Name: password_reset_tokens; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.password_reset_tokens (
    email character varying(255) NOT NULL,
    token character varying(255) NOT NULL,
    created_at timestamp(0) without time zone
);


--
-- Name: payments; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.payments (
    id bigint NOT NULL,
    order_id bigint NOT NULL,
    provider character varying(255) NOT NULL,
    transaction_id character varying(255),
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    amount numeric(10,2) NOT NULL,
    payload jsonb,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: payments_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.payments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: payments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.payments_id_seq OWNED BY public.payments.id;


--
-- Name: personal_access_tokens; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.personal_access_tokens (
    id bigint NOT NULL,
    tokenable_type character varying(255) NOT NULL,
    tokenable_id bigint NOT NULL,
    name text NOT NULL,
    token character varying(64) NOT NULL,
    abilities text,
    last_used_at timestamp(0) without time zone,
    expires_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: personal_access_tokens_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.personal_access_tokens_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: personal_access_tokens_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.personal_access_tokens_id_seq OWNED BY public.personal_access_tokens.id;


--
-- Name: product_inventory; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.product_inventory (
    id bigint NOT NULL,
    product_id bigint NOT NULL,
    provider_id bigint NOT NULL,
    current_stock numeric(10,3) DEFAULT '0'::numeric NOT NULL,
    reserved_stock numeric(10,3) DEFAULT '0'::numeric NOT NULL,
    allow_negative_stock boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: product_inventory_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.product_inventory_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: product_inventory_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.product_inventory_id_seq OWNED BY public.product_inventory.id;


--
-- Name: product_option_groups; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.product_option_groups (
    id bigint NOT NULL,
    product_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    min_choices integer DEFAULT 0 NOT NULL,
    max_choices integer DEFAULT 1 NOT NULL,
    is_required boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: product_option_groups_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.product_option_groups_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: product_option_groups_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.product_option_groups_id_seq OWNED BY public.product_option_groups.id;


--
-- Name: product_options; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.product_options (
    id bigint NOT NULL,
    option_group_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    extra_price numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    is_available boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: product_options_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.product_options_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: product_options_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.product_options_id_seq OWNED BY public.product_options.id;


--
-- Name: product_recipes; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.product_recipes (
    id bigint NOT NULL,
    product_id bigint NOT NULL,
    supply_id bigint NOT NULL,
    quantity_required numeric(10,3) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT product_recipes_quantity_required_check CHECK ((quantity_required > (0)::numeric))
);


--
-- Name: product_recipes_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.product_recipes_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: product_recipes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.product_recipes_id_seq OWNED BY public.product_recipes.id;


--
-- Name: product_variants; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.product_variants (
    id bigint NOT NULL,
    product_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    price numeric(10,2) NOT NULL,
    is_available boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: product_variants_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.product_variants_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: product_variants_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.product_variants_id_seq OWNED BY public.product_variants.id;


--
-- Name: products; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.products (
    id bigint NOT NULL,
    provider_id bigint NOT NULL,
    category_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    description text,
    price numeric(10,2) NOT NULL,
    image_path character varying(255),
    is_available boolean DEFAULT true NOT NULL,
    sort_order integer DEFAULT 0 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    unit_of_measure_id bigint,
    track_stock boolean DEFAULT false NOT NULL,
    min_stock_alert numeric(10,3)
);


--
-- Name: products_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.products_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: products_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.products_id_seq OWNED BY public.products.id;


--
-- Name: provider_drivers; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.provider_drivers (
    id bigint NOT NULL,
    provider_id bigint NOT NULL,
    user_id bigint NOT NULL,
    status character varying(30) DEFAULT 'active'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: provider_drivers_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.provider_drivers_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: provider_drivers_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.provider_drivers_id_seq OWNED BY public.provider_drivers.id;


--
-- Name: provider_images; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.provider_images (
    id bigint NOT NULL,
    provider_id bigint NOT NULL,
    image_path character varying(255) NOT NULL,
    image_type character varying(30) DEFAULT 'banner'::character varying NOT NULL,
    is_primary boolean DEFAULT false NOT NULL,
    sort_order integer DEFAULT 0 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: provider_images_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.provider_images_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: provider_images_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.provider_images_id_seq OWNED BY public.provider_images.id;


--
-- Name: provider_service_types; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.provider_service_types (
    id bigint NOT NULL,
    provider_id bigint NOT NULL,
    service_type_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: provider_service_types_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.provider_service_types_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: provider_service_types_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.provider_service_types_id_seq OWNED BY public.provider_service_types.id;


--
-- Name: provider_services; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.provider_services (
    id bigint NOT NULL,
    provider_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    sort_order integer DEFAULT 0 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    description text
);


--
-- Name: provider_services_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.provider_services_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: provider_services_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.provider_services_id_seq OWNED BY public.provider_services.id;


--
-- Name: provider_subgroups; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.provider_subgroups (
    id bigint NOT NULL,
    provider_id bigint NOT NULL,
    subgroup_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: provider_subgroups_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.provider_subgroups_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: provider_subgroups_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.provider_subgroups_id_seq OWNED BY public.provider_subgroups.id;


--
-- Name: providers; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.providers (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    category_id bigint,
    business_name character varying(255) NOT NULL,
    description text,
    banner_image character varying(255),
    rating numeric(3,2),
    promo character varying(255),
    phone character varying(30),
    whatsapp character varying(30),
    zone character varying(255),
    hours json,
    is_active boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    has_inventory_control boolean DEFAULT false NOT NULL,
    balance numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    is_verified boolean DEFAULT false NOT NULL,
    base_visit_fee numeric(10,2),
    rating_count integer DEFAULT 0 NOT NULL,
    delivery_mode character varying(30) DEFAULT 'own'::character varying NOT NULL,
    trusted_drivers_enabled boolean DEFAULT false NOT NULL,
    delivery_fee_mode character varying(20) DEFAULT 'global'::character varying NOT NULL,
    delivery_fee_flat numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    delivery_fee_base numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    delivery_fee_per_km numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    delivery_fee_min numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    delivery_fee_max numeric(10,2) DEFAULT '0'::numeric NOT NULL
);


--
-- Name: providers_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.providers_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: providers_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.providers_id_seq OWNED BY public.providers.id;


--
-- Name: push_tokens; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.push_tokens (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    token character varying(255) NOT NULL,
    platform character varying(20),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: push_tokens_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.push_tokens_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: push_tokens_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.push_tokens_id_seq OWNED BY public.push_tokens.id;


--
-- Name: regions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.regions (
    id bigint NOT NULL,
    country_id bigint NOT NULL,
    parent_id bigint,
    code character varying(20),
    name character varying(255) NOT NULL,
    type character varying(30) NOT NULL,
    sort_order integer DEFAULT 0 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: regions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.regions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: regions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.regions_id_seq OWNED BY public.regions.id;


--
-- Name: service_orders; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.service_orders (
    id bigint NOT NULL,
    customer_id bigint NOT NULL,
    sub_group_id bigint NOT NULL,
    merchant_id bigint,
    order_type character varying(20) NOT NULL,
    status character varying(30) DEFAULT 'REQUESTED'::character varying NOT NULL,
    latitude numeric(10,7),
    longitude numeric(10,7),
    scheduled_at timestamp(0) without time zone,
    visit_fee numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    additional_cost numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    total_amount numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    escrow_status character varying(20) DEFAULT 'HOLDING'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    description text,
    photo_path character varying(255),
    audio_path character varying(255),
    estimate_details text,
    fulfillment character varying(30),
    payment_method character varying(30),
    payment_mode character varying(20),
    deposit_amount numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    completion_photo_path character varying(255),
    completed_at timestamp(0) without time zone,
    commission_amount numeric(10,2),
    address text,
    address_street character varying(150),
    address_unit character varying(50),
    address_neighborhood character varying(100),
    reminder_channel character varying(20),
    quote_context json,
    accepted_at timestamp(0) without time zone,
    started_at timestamp(0) without time zone
);


--
-- Name: service_orders_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.service_orders_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: service_orders_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.service_orders_id_seq OWNED BY public.service_orders.id;


--
-- Name: service_quotes; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.service_quotes (
    id bigint NOT NULL,
    service_order_id bigint NOT NULL,
    provider_id bigint,
    proposed_price numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    details text,
    estimated_time character varying(255),
    attachments_json json,
    status character varying(20) DEFAULT 'PENDING'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: service_quotes_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.service_quotes_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: service_quotes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.service_quotes_id_seq OWNED BY public.service_quotes.id;


--
-- Name: service_ratings; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.service_ratings (
    id bigint NOT NULL,
    service_order_id bigint NOT NULL,
    reviewer_id bigint NOT NULL,
    ratee_id bigint NOT NULL,
    stars smallint NOT NULL,
    comment text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: service_ratings_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.service_ratings_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: service_ratings_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.service_ratings_id_seq OWNED BY public.service_ratings.id;


--
-- Name: service_types; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.service_types (
    id bigint NOT NULL,
    code character varying(30) NOT NULL,
    name character varying(255) NOT NULL,
    description character varying(255),
    is_active boolean DEFAULT true NOT NULL,
    sort_order integer DEFAULT 0 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: service_types_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.service_types_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: service_types_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.service_types_id_seq OWNED BY public.service_types.id;


--
-- Name: sessions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.sessions (
    id character varying(255) NOT NULL,
    user_id bigint,
    ip_address character varying(45),
    user_agent text,
    payload text NOT NULL,
    last_activity integer NOT NULL
);


--
-- Name: sub_groups; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.sub_groups (
    id bigint NOT NULL,
    description character varying(255) NOT NULL,
    group_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: sub_groups_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.sub_groups_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: sub_groups_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.sub_groups_id_seq OWNED BY public.sub_groups.id;


--
-- Name: subscription_payments; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.subscription_payments (
    id bigint NOT NULL,
    customer_subscription_id bigint NOT NULL,
    order_id bigint,
    provider_id bigint NOT NULL,
    user_id bigint NOT NULL,
    gateway character varying(50) DEFAULT 'manual'::character varying NOT NULL,
    gateway_payment_id character varying(255),
    status character varying(255) NOT NULL,
    attempt smallint DEFAULT '1'::smallint NOT NULL,
    subtotal numeric(10,2) NOT NULL,
    discount numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    delivery_fee numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    total numeric(10,2) NOT NULL,
    currency character varying(3) DEFAULT 'ARS'::character varying NOT NULL,
    period_start date NOT NULL,
    period_end date NOT NULL,
    paid_at timestamp(0) without time zone,
    failure_reason character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT subscription_payments_status_check CHECK (((status)::text = ANY ((ARRAY['PENDING'::character varying, 'PAID'::character varying, 'FAILED'::character varying, 'REFUNDED'::character varying, 'CANCELLED'::character varying])::text[])))
);


--
-- Name: subscription_payments_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.subscription_payments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: subscription_payments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.subscription_payments_id_seq OWNED BY public.subscription_payments.id;


--
-- Name: subscription_plan_items; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.subscription_plan_items (
    id bigint NOT NULL,
    subscription_plan_id bigint NOT NULL,
    product_id bigint NOT NULL,
    quantity integer DEFAULT 1 NOT NULL
);


--
-- Name: subscription_plan_items_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.subscription_plan_items_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: subscription_plan_items_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.subscription_plan_items_id_seq OWNED BY public.subscription_plan_items.id;


--
-- Name: subscription_plans; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.subscription_plans (
    id bigint NOT NULL,
    provider_id bigint NOT NULL,
    title character varying(255) NOT NULL,
    description text,
    frequency character varying(255) NOT NULL,
    price numeric(10,2) NOT NULL,
    discount_percentage numeric(5,2) DEFAULT '0'::numeric NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    capacity integer,
    CONSTRAINT subscription_plans_frequency_check CHECK (((frequency)::text = ANY ((ARRAY['DAILY'::character varying, 'WEEKLY'::character varying, 'BIWEEKLY'::character varying, 'MONTHLY'::character varying])::text[])))
);


--
-- Name: subscription_plans_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.subscription_plans_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: subscription_plans_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.subscription_plans_id_seq OWNED BY public.subscription_plans.id;


--
-- Name: supplies; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.supplies (
    id bigint NOT NULL,
    provider_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    unit_of_measure_id bigint NOT NULL,
    cost_per_unit numeric(10,2) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: supplies_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.supplies_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: supplies_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.supplies_id_seq OWNED BY public.supplies.id;


--
-- Name: type_users; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.type_users (
    id bigint NOT NULL,
    description character varying(255) NOT NULL,
    created_at timestamp without time zone,
    updated_at timestamp without time zone
);


--
-- Name: type_users_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.type_users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: type_users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.type_users_id_seq OWNED BY public.type_users.id;


--
-- Name: unit_of_measures; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.unit_of_measures (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    symbol character varying(20) NOT NULL,
    base_conversion_factor numeric(10,4) DEFAULT '1'::numeric NOT NULL,
    is_integer_only boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: unit_of_measures_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.unit_of_measures_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: unit_of_measures_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.unit_of_measures_id_seq OWNED BY public.unit_of_measures.id;


--
-- Name: user_notifications; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.user_notifications (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    type character varying(50) NOT NULL,
    title character varying(255) NOT NULL,
    body character varying(500),
    data json,
    read_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: user_notifications_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.user_notifications_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: user_notifications_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.user_notifications_id_seq OWNED BY public.user_notifications.id;


--
-- Name: user_statuses; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.user_statuses (
    id bigint NOT NULL,
    status character varying(255) NOT NULL
);


--
-- Name: user_statuses_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.user_statuses_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: user_statuses_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.user_statuses_id_seq OWNED BY public.user_statuses.id;


--
-- Name: users; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.users (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    email character varying(255) NOT NULL,
    password character varying(255) NOT NULL,
    user_status_id bigint,
    email_verified_at timestamp without time zone,
    remember_token character varying(100),
    created_at timestamp without time zone,
    updated_at timestamp without time zone,
    type_user_id bigint,
    phone character varying(30),
    vehicle_type character varying(30),
    vehicle_plate character varying(20)
);


--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- Name: wallet_transactions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.wallet_transactions (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    order_id bigint,
    amount numeric(12,2) NOT NULL,
    type character varying(30) NOT NULL,
    status character varying(30) DEFAULT 'completed'::character varying NOT NULL,
    description text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: wallet_transactions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.wallet_transactions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: wallet_transactions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.wallet_transactions_id_seq OWNED BY public.wallet_transactions.id;


--
-- Name: addresses id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.addresses ALTER COLUMN id SET DEFAULT nextval('public.addresses_id_seq'::regclass);


--
-- Name: categories id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.categories ALTER COLUMN id SET DEFAULT nextval('public.categories_id_seq'::regclass);


--
-- Name: contact_inquiries id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contact_inquiries ALTER COLUMN id SET DEFAULT nextval('public.contact_inquiries_id_seq'::regclass);


--
-- Name: countries id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.countries ALTER COLUMN id SET DEFAULT nextval('public.countries_id_seq'::regclass);


--
-- Name: currencies id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.currencies ALTER COLUMN id SET DEFAULT nextval('public.currencies_id_seq'::regclass);


--
-- Name: customer_subscription_items id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.customer_subscription_items ALTER COLUMN id SET DEFAULT nextval('public.customer_subscription_items_id_seq'::regclass);


--
-- Name: customer_subscriptions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.customer_subscriptions ALTER COLUMN id SET DEFAULT nextval('public.customer_subscriptions_id_seq'::regclass);


--
-- Name: driver_wallets id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.driver_wallets ALTER COLUMN id SET DEFAULT nextval('public.driver_wallets_id_seq'::regclass);


--
-- Name: driver_withdrawal_requests id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.driver_withdrawal_requests ALTER COLUMN id SET DEFAULT nextval('public.driver_withdrawal_requests_id_seq'::regclass);


--
-- Name: failed_jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs ALTER COLUMN id SET DEFAULT nextval('public.failed_jobs_id_seq'::regclass);


--
-- Name: favorites id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.favorites ALTER COLUMN id SET DEFAULT nextval('public.favorites_id_seq'::regclass);


--
-- Name: group_statuses id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.group_statuses ALTER COLUMN id SET DEFAULT nextval('public.group_statuses_id_seq'::regclass);


--
-- Name: groups id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.groups ALTER COLUMN id SET DEFAULT nextval('public.groups_id_seq'::regclass);


--
-- Name: inventory_movements id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.inventory_movements ALTER COLUMN id SET DEFAULT nextval('public.inventory_movements_id_seq'::regclass);


--
-- Name: jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jobs ALTER COLUMN id SET DEFAULT nextval('public.jobs_id_seq'::regclass);


--
-- Name: marketing_campaign_rules id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.marketing_campaign_rules ALTER COLUMN id SET DEFAULT nextval('public.marketing_campaign_rules_id_seq'::regclass);


--
-- Name: marketing_logs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.marketing_logs ALTER COLUMN id SET DEFAULT nextval('public.marketing_logs_id_seq'::regclass);


--
-- Name: migrations id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations ALTER COLUMN id SET DEFAULT nextval('public.migrations_id_seq'::regclass);


--
-- Name: modules id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.modules ALTER COLUMN id SET DEFAULT nextval('public.modules_id_seq'::regclass);


--
-- Name: order_item_options id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.order_item_options ALTER COLUMN id SET DEFAULT nextval('public.order_item_options_id_seq'::regclass);


--
-- Name: order_items id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.order_items ALTER COLUMN id SET DEFAULT nextval('public.order_items_id_seq'::regclass);


--
-- Name: order_ratings id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.order_ratings ALTER COLUMN id SET DEFAULT nextval('public.order_ratings_id_seq'::regclass);


--
-- Name: orders id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.orders ALTER COLUMN id SET DEFAULT nextval('public.orders_id_seq'::regclass);


--
-- Name: page_type_user id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.page_type_user ALTER COLUMN id SET DEFAULT nextval('public.page_type_user_id_seq'::regclass);


--
-- Name: page_user id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.page_user ALTER COLUMN id SET DEFAULT nextval('public.page_user_id_seq'::regclass);


--
-- Name: pages id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pages ALTER COLUMN id SET DEFAULT nextval('public.pages_id_seq'::regclass);


--
-- Name: password_reset_requests id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.password_reset_requests ALTER COLUMN id SET DEFAULT nextval('public.password_reset_requests_id_seq'::regclass);


--
-- Name: payments id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payments ALTER COLUMN id SET DEFAULT nextval('public.payments_id_seq'::regclass);


--
-- Name: personal_access_tokens id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.personal_access_tokens ALTER COLUMN id SET DEFAULT nextval('public.personal_access_tokens_id_seq'::regclass);


--
-- Name: product_inventory id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_inventory ALTER COLUMN id SET DEFAULT nextval('public.product_inventory_id_seq'::regclass);


--
-- Name: product_option_groups id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_option_groups ALTER COLUMN id SET DEFAULT nextval('public.product_option_groups_id_seq'::regclass);


--
-- Name: product_options id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_options ALTER COLUMN id SET DEFAULT nextval('public.product_options_id_seq'::regclass);


--
-- Name: product_recipes id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_recipes ALTER COLUMN id SET DEFAULT nextval('public.product_recipes_id_seq'::regclass);


--
-- Name: product_variants id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_variants ALTER COLUMN id SET DEFAULT nextval('public.product_variants_id_seq'::regclass);


--
-- Name: products id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.products ALTER COLUMN id SET DEFAULT nextval('public.products_id_seq'::regclass);


--
-- Name: provider_drivers id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.provider_drivers ALTER COLUMN id SET DEFAULT nextval('public.provider_drivers_id_seq'::regclass);


--
-- Name: provider_images id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.provider_images ALTER COLUMN id SET DEFAULT nextval('public.provider_images_id_seq'::regclass);


--
-- Name: provider_service_types id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.provider_service_types ALTER COLUMN id SET DEFAULT nextval('public.provider_service_types_id_seq'::regclass);


--
-- Name: provider_services id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.provider_services ALTER COLUMN id SET DEFAULT nextval('public.provider_services_id_seq'::regclass);


--
-- Name: provider_subgroups id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.provider_subgroups ALTER COLUMN id SET DEFAULT nextval('public.provider_subgroups_id_seq'::regclass);


--
-- Name: providers id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.providers ALTER COLUMN id SET DEFAULT nextval('public.providers_id_seq'::regclass);


--
-- Name: push_tokens id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.push_tokens ALTER COLUMN id SET DEFAULT nextval('public.push_tokens_id_seq'::regclass);


--
-- Name: regions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.regions ALTER COLUMN id SET DEFAULT nextval('public.regions_id_seq'::regclass);


--
-- Name: service_orders id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.service_orders ALTER COLUMN id SET DEFAULT nextval('public.service_orders_id_seq'::regclass);


--
-- Name: service_quotes id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.service_quotes ALTER COLUMN id SET DEFAULT nextval('public.service_quotes_id_seq'::regclass);


--
-- Name: service_ratings id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.service_ratings ALTER COLUMN id SET DEFAULT nextval('public.service_ratings_id_seq'::regclass);


--
-- Name: service_types id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.service_types ALTER COLUMN id SET DEFAULT nextval('public.service_types_id_seq'::regclass);


--
-- Name: sub_groups id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sub_groups ALTER COLUMN id SET DEFAULT nextval('public.sub_groups_id_seq'::regclass);


--
-- Name: subscription_payments id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.subscription_payments ALTER COLUMN id SET DEFAULT nextval('public.subscription_payments_id_seq'::regclass);


--
-- Name: subscription_plan_items id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.subscription_plan_items ALTER COLUMN id SET DEFAULT nextval('public.subscription_plan_items_id_seq'::regclass);


--
-- Name: subscription_plans id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.subscription_plans ALTER COLUMN id SET DEFAULT nextval('public.subscription_plans_id_seq'::regclass);


--
-- Name: supplies id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.supplies ALTER COLUMN id SET DEFAULT nextval('public.supplies_id_seq'::regclass);


--
-- Name: type_users id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.type_users ALTER COLUMN id SET DEFAULT nextval('public.type_users_id_seq'::regclass);


--
-- Name: unit_of_measures id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.unit_of_measures ALTER COLUMN id SET DEFAULT nextval('public.unit_of_measures_id_seq'::regclass);


--
-- Name: user_notifications id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_notifications ALTER COLUMN id SET DEFAULT nextval('public.user_notifications_id_seq'::regclass);


--
-- Name: user_statuses id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_statuses ALTER COLUMN id SET DEFAULT nextval('public.user_statuses_id_seq'::regclass);


--
-- Name: users id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- Name: wallet_transactions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.wallet_transactions ALTER COLUMN id SET DEFAULT nextval('public.wallet_transactions_id_seq'::regclass);


--
-- Name: addresses addresses_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.addresses
    ADD CONSTRAINT addresses_pkey PRIMARY KEY (id);


--
-- Name: cache_locks cache_locks_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cache_locks
    ADD CONSTRAINT cache_locks_pkey PRIMARY KEY (key);


--
-- Name: cache cache_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cache
    ADD CONSTRAINT cache_pkey PRIMARY KEY (key);


--
-- Name: categories categories_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.categories
    ADD CONSTRAINT categories_pkey PRIMARY KEY (id);


--
-- Name: contact_inquiries contact_inquiries_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contact_inquiries
    ADD CONSTRAINT contact_inquiries_pkey PRIMARY KEY (id);


--
-- Name: countries countries_iso_code_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.countries
    ADD CONSTRAINT countries_iso_code_unique UNIQUE (iso_code);


--
-- Name: countries countries_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.countries
    ADD CONSTRAINT countries_pkey PRIMARY KEY (id);


--
-- Name: currencies currencies_code_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.currencies
    ADD CONSTRAINT currencies_code_unique UNIQUE (code);


--
-- Name: currencies currencies_country_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.currencies
    ADD CONSTRAINT currencies_country_id_unique UNIQUE (country_id);


--
-- Name: currencies currencies_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.currencies
    ADD CONSTRAINT currencies_pkey PRIMARY KEY (id);


--
-- Name: customer_subscription_items customer_subscription_items_customer_subscription_id_product_id; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.customer_subscription_items
    ADD CONSTRAINT customer_subscription_items_customer_subscription_id_product_id UNIQUE (customer_subscription_id, product_id);


--
-- Name: customer_subscription_items customer_subscription_items_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.customer_subscription_items
    ADD CONSTRAINT customer_subscription_items_pkey PRIMARY KEY (id);


--
-- Name: customer_subscriptions customer_subscriptions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.customer_subscriptions
    ADD CONSTRAINT customer_subscriptions_pkey PRIMARY KEY (id);


--
-- Name: driver_wallets driver_wallets_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.driver_wallets
    ADD CONSTRAINT driver_wallets_pkey PRIMARY KEY (id);


--
-- Name: driver_wallets driver_wallets_user_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.driver_wallets
    ADD CONSTRAINT driver_wallets_user_id_unique UNIQUE (user_id);


--
-- Name: driver_withdrawal_requests driver_withdrawal_requests_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.driver_withdrawal_requests
    ADD CONSTRAINT driver_withdrawal_requests_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_uuid_unique UNIQUE (uuid);


--
-- Name: favorites favorites_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.favorites
    ADD CONSTRAINT favorites_pkey PRIMARY KEY (id);


--
-- Name: favorites favorites_user_id_provider_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.favorites
    ADD CONSTRAINT favorites_user_id_provider_id_unique UNIQUE (user_id, provider_id);


--
-- Name: group_statuses group_statuses_description_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.group_statuses
    ADD CONSTRAINT group_statuses_description_unique UNIQUE (description);


--
-- Name: group_statuses group_statuses_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.group_statuses
    ADD CONSTRAINT group_statuses_pkey PRIMARY KEY (id);


--
-- Name: groups groups_description_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.groups
    ADD CONSTRAINT groups_description_unique UNIQUE (description);


--
-- Name: groups groups_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.groups
    ADD CONSTRAINT groups_pkey PRIMARY KEY (id);


--
-- Name: inventory_movements inventory_movements_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.inventory_movements
    ADD CONSTRAINT inventory_movements_pkey PRIMARY KEY (id);


--
-- Name: job_batches job_batches_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.job_batches
    ADD CONSTRAINT job_batches_pkey PRIMARY KEY (id);


--
-- Name: jobs jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jobs
    ADD CONSTRAINT jobs_pkey PRIMARY KEY (id);


--
-- Name: marketing_campaign_rules marketing_campaign_rules_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.marketing_campaign_rules
    ADD CONSTRAINT marketing_campaign_rules_pkey PRIMARY KEY (id);


--
-- Name: marketing_logs marketing_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.marketing_logs
    ADD CONSTRAINT marketing_logs_pkey PRIMARY KEY (id);


--
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- Name: modules modules_description_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.modules
    ADD CONSTRAINT modules_description_key UNIQUE (description);


--
-- Name: modules modules_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.modules
    ADD CONSTRAINT modules_pkey PRIMARY KEY (id);


--
-- Name: order_item_options order_item_options_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.order_item_options
    ADD CONSTRAINT order_item_options_pkey PRIMARY KEY (id);


--
-- Name: order_items order_items_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.order_items
    ADD CONSTRAINT order_items_pkey PRIMARY KEY (id);


--
-- Name: order_ratings order_ratings_order_id_reviewer_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.order_ratings
    ADD CONSTRAINT order_ratings_order_id_reviewer_id_unique UNIQUE (order_id, reviewer_id);


--
-- Name: order_ratings order_ratings_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.order_ratings
    ADD CONSTRAINT order_ratings_pkey PRIMARY KEY (id);


--
-- Name: orders orders_order_number_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.orders
    ADD CONSTRAINT orders_order_number_unique UNIQUE (order_number);


--
-- Name: orders orders_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.orders
    ADD CONSTRAINT orders_pkey PRIMARY KEY (id);


--
-- Name: page_user page_user_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.page_user
    ADD CONSTRAINT page_user_pkey PRIMARY KEY (id);


--
-- Name: page_user page_user_user_id_page_id_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.page_user
    ADD CONSTRAINT page_user_user_id_page_id_key UNIQUE (user_id, page_id);


--
-- Name: pages pages_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pages
    ADD CONSTRAINT pages_pkey PRIMARY KEY (id);


--
-- Name: page_type_user paginasptiposusuarios_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.page_type_user
    ADD CONSTRAINT paginasptiposusuarios_pkey PRIMARY KEY (id);


--
-- Name: page_type_user paginasptiposusuarios_type_user_id_page_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.page_type_user
    ADD CONSTRAINT paginasptiposusuarios_type_user_id_page_id_unique UNIQUE (type_user_id, page_id);


--
-- Name: password_reset_requests password_reset_requests_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.password_reset_requests
    ADD CONSTRAINT password_reset_requests_pkey PRIMARY KEY (id);


--
-- Name: password_reset_requests password_reset_requests_token_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.password_reset_requests
    ADD CONSTRAINT password_reset_requests_token_unique UNIQUE (token);


--
-- Name: password_reset_tokens password_reset_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.password_reset_tokens
    ADD CONSTRAINT password_reset_tokens_pkey PRIMARY KEY (email);


--
-- Name: payments payments_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payments
    ADD CONSTRAINT payments_pkey PRIMARY KEY (id);


--
-- Name: personal_access_tokens personal_access_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.personal_access_tokens
    ADD CONSTRAINT personal_access_tokens_pkey PRIMARY KEY (id);


--
-- Name: personal_access_tokens personal_access_tokens_token_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.personal_access_tokens
    ADD CONSTRAINT personal_access_tokens_token_unique UNIQUE (token);


--
-- Name: product_inventory product_inventory_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_inventory
    ADD CONSTRAINT product_inventory_pkey PRIMARY KEY (id);


--
-- Name: product_option_groups product_option_groups_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_option_groups
    ADD CONSTRAINT product_option_groups_pkey PRIMARY KEY (id);


--
-- Name: product_options product_options_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_options
    ADD CONSTRAINT product_options_pkey PRIMARY KEY (id);


--
-- Name: product_recipes product_recipes_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_recipes
    ADD CONSTRAINT product_recipes_pkey PRIMARY KEY (id);


--
-- Name: product_recipes product_recipes_product_id_supply_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_recipes
    ADD CONSTRAINT product_recipes_product_id_supply_id_unique UNIQUE (product_id, supply_id);


--
-- Name: product_variants product_variants_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_variants
    ADD CONSTRAINT product_variants_pkey PRIMARY KEY (id);


--
-- Name: products products_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.products
    ADD CONSTRAINT products_pkey PRIMARY KEY (id);


--
-- Name: provider_drivers provider_drivers_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.provider_drivers
    ADD CONSTRAINT provider_drivers_pkey PRIMARY KEY (id);


--
-- Name: provider_drivers provider_drivers_provider_id_user_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.provider_drivers
    ADD CONSTRAINT provider_drivers_provider_id_user_id_unique UNIQUE (provider_id, user_id);


--
-- Name: provider_images provider_images_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.provider_images
    ADD CONSTRAINT provider_images_pkey PRIMARY KEY (id);


--
-- Name: provider_service_types provider_service_types_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.provider_service_types
    ADD CONSTRAINT provider_service_types_pkey PRIMARY KEY (id);


--
-- Name: provider_service_types provider_service_types_provider_id_service_type_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.provider_service_types
    ADD CONSTRAINT provider_service_types_provider_id_service_type_id_unique UNIQUE (provider_id, service_type_id);


--
-- Name: provider_services provider_services_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.provider_services
    ADD CONSTRAINT provider_services_pkey PRIMARY KEY (id);


--
-- Name: provider_subgroups provider_subgroups_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.provider_subgroups
    ADD CONSTRAINT provider_subgroups_pkey PRIMARY KEY (id);


--
-- Name: provider_subgroups provider_subgroups_provider_id_subgroup_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.provider_subgroups
    ADD CONSTRAINT provider_subgroups_provider_id_subgroup_id_unique UNIQUE (provider_id, subgroup_id);


--
-- Name: providers providers_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.providers
    ADD CONSTRAINT providers_pkey PRIMARY KEY (id);


--
-- Name: providers providers_user_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.providers
    ADD CONSTRAINT providers_user_id_unique UNIQUE (user_id);


--
-- Name: push_tokens push_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.push_tokens
    ADD CONSTRAINT push_tokens_pkey PRIMARY KEY (id);


--
-- Name: push_tokens push_tokens_token_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.push_tokens
    ADD CONSTRAINT push_tokens_token_unique UNIQUE (token);


--
-- Name: regions regions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.regions
    ADD CONSTRAINT regions_pkey PRIMARY KEY (id);


--
-- Name: service_orders service_orders_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.service_orders
    ADD CONSTRAINT service_orders_pkey PRIMARY KEY (id);


--
-- Name: service_quotes service_quotes_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.service_quotes
    ADD CONSTRAINT service_quotes_pkey PRIMARY KEY (id);


--
-- Name: service_ratings service_ratings_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.service_ratings
    ADD CONSTRAINT service_ratings_pkey PRIMARY KEY (id);


--
-- Name: service_ratings service_ratings_service_order_id_reviewer_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.service_ratings
    ADD CONSTRAINT service_ratings_service_order_id_reviewer_id_unique UNIQUE (service_order_id, reviewer_id);


--
-- Name: service_types service_types_code_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.service_types
    ADD CONSTRAINT service_types_code_unique UNIQUE (code);


--
-- Name: service_types service_types_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.service_types
    ADD CONSTRAINT service_types_pkey PRIMARY KEY (id);


--
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);


--
-- Name: sub_groups sub_groups_description_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sub_groups
    ADD CONSTRAINT sub_groups_description_unique UNIQUE (description);


--
-- Name: sub_groups sub_groups_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sub_groups
    ADD CONSTRAINT sub_groups_pkey PRIMARY KEY (id);


--
-- Name: subscription_payments subscription_payments_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.subscription_payments
    ADD CONSTRAINT subscription_payments_pkey PRIMARY KEY (id);


--
-- Name: subscription_plan_items subscription_plan_items_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.subscription_plan_items
    ADD CONSTRAINT subscription_plan_items_pkey PRIMARY KEY (id);


--
-- Name: subscription_plan_items subscription_plan_items_subscription_plan_id_product_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.subscription_plan_items
    ADD CONSTRAINT subscription_plan_items_subscription_plan_id_product_id_unique UNIQUE (subscription_plan_id, product_id);


--
-- Name: subscription_plans subscription_plans_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.subscription_plans
    ADD CONSTRAINT subscription_plans_pkey PRIMARY KEY (id);


--
-- Name: supplies supplies_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.supplies
    ADD CONSTRAINT supplies_pkey PRIMARY KEY (id);


--
-- Name: supplies supplies_provider_id_name_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.supplies
    ADD CONSTRAINT supplies_provider_id_name_unique UNIQUE (provider_id, name);


--
-- Name: type_users type_users_description_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.type_users
    ADD CONSTRAINT type_users_description_key UNIQUE (description);


--
-- Name: type_users type_users_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.type_users
    ADD CONSTRAINT type_users_pkey PRIMARY KEY (id);


--
-- Name: unit_of_measures unit_of_measures_name_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.unit_of_measures
    ADD CONSTRAINT unit_of_measures_name_unique UNIQUE (name);


--
-- Name: unit_of_measures unit_of_measures_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.unit_of_measures
    ADD CONSTRAINT unit_of_measures_pkey PRIMARY KEY (id);


--
-- Name: unit_of_measures unit_of_measures_symbol_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.unit_of_measures
    ADD CONSTRAINT unit_of_measures_symbol_unique UNIQUE (symbol);


--
-- Name: user_notifications user_notifications_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_notifications
    ADD CONSTRAINT user_notifications_pkey PRIMARY KEY (id);


--
-- Name: user_statuses user_statuses_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_statuses
    ADD CONSTRAINT user_statuses_pkey PRIMARY KEY (id);


--
-- Name: user_statuses user_statuses_status_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_statuses
    ADD CONSTRAINT user_statuses_status_unique UNIQUE (status);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: wallet_transactions wallet_transactions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.wallet_transactions
    ADD CONSTRAINT wallet_transactions_pkey PRIMARY KEY (id);


--
-- Name: addresses_country_id_department_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX addresses_country_id_department_id_index ON public.addresses USING btree (country_id, department_id);


--
-- Name: addresses_country_id_province_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX addresses_country_id_province_id_index ON public.addresses USING btree (country_id, province_id);


--
-- Name: addresses_department_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX addresses_department_id_index ON public.addresses USING btree (department_id);


--
-- Name: addresses_province_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX addresses_province_id_index ON public.addresses USING btree (province_id);


--
-- Name: addresses_user_id_is_primary_sort_order_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX addresses_user_id_is_primary_sort_order_index ON public.addresses USING btree (user_id, is_primary, sort_order);


--
-- Name: cache_expiration_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cache_expiration_index ON public.cache USING btree (expiration);


--
-- Name: cache_locks_expiration_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cache_locks_expiration_index ON public.cache_locks USING btree (expiration);


--
-- Name: categories_provider_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX categories_provider_id_index ON public.categories USING btree (provider_id);


--
-- Name: contact_inquiries_status_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contact_inquiries_status_created_at_index ON public.contact_inquiries USING btree (status, created_at);


--
-- Name: currencies_code_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX currencies_code_is_active_index ON public.currencies USING btree (code, is_active);


--
-- Name: customer_subscription_items_product_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX customer_subscription_items_product_id_index ON public.customer_subscription_items USING btree (product_id);


--
-- Name: customer_subscriptions_delivery_address_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX customer_subscriptions_delivery_address_id_index ON public.customer_subscriptions USING btree (delivery_address_id);


--
-- Name: customer_subscriptions_next_delivery_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX customer_subscriptions_next_delivery_date_index ON public.customer_subscriptions USING btree (next_delivery_date);


--
-- Name: customer_subscriptions_provider_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX customer_subscriptions_provider_id_index ON public.customer_subscriptions USING btree (provider_id);


--
-- Name: customer_subscriptions_provider_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX customer_subscriptions_provider_id_status_index ON public.customer_subscriptions USING btree (provider_id, status);


--
-- Name: customer_subscriptions_subscription_plan_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX customer_subscriptions_subscription_plan_id_index ON public.customer_subscriptions USING btree (subscription_plan_id);


--
-- Name: customer_subscriptions_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX customer_subscriptions_user_id_index ON public.customer_subscriptions USING btree (user_id);


--
-- Name: customer_subscriptions_user_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX customer_subscriptions_user_id_status_index ON public.customer_subscriptions USING btree (user_id, status);


--
-- Name: driver_withdrawal_requests_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX driver_withdrawal_requests_status_index ON public.driver_withdrawal_requests USING btree (status);


--
-- Name: driver_withdrawal_requests_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX driver_withdrawal_requests_user_id_index ON public.driver_withdrawal_requests USING btree (user_id);


--
-- Name: favorites_provider_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX favorites_provider_id_index ON public.favorites USING btree (provider_id);


--
-- Name: inventory_movements_order_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX inventory_movements_order_id_index ON public.inventory_movements USING btree (order_id);


--
-- Name: inventory_movements_product_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX inventory_movements_product_id_index ON public.inventory_movements USING btree (product_id);


--
-- Name: inventory_movements_provider_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX inventory_movements_provider_id_index ON public.inventory_movements USING btree (provider_id);


--
-- Name: inventory_movements_unit_of_measure_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX inventory_movements_unit_of_measure_id_index ON public.inventory_movements USING btree (unit_of_measure_id);


--
-- Name: jobs_queue_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX jobs_queue_index ON public.jobs USING btree (queue);


--
-- Name: marketing_campaign_rules_is_enabled_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX marketing_campaign_rules_is_enabled_index ON public.marketing_campaign_rules USING btree (is_enabled);


--
-- Name: marketing_campaign_rules_provider_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX marketing_campaign_rules_provider_id_index ON public.marketing_campaign_rules USING btree (provider_id);


--
-- Name: marketing_campaign_rules_provider_id_is_enabled_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX marketing_campaign_rules_provider_id_is_enabled_index ON public.marketing_campaign_rules USING btree (provider_id, is_enabled);


--
-- Name: marketing_campaign_rules_rule_type_is_enabled_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX marketing_campaign_rules_rule_type_is_enabled_index ON public.marketing_campaign_rules USING btree (rule_type, is_enabled);


--
-- Name: marketing_logs_campaign_rule_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX marketing_logs_campaign_rule_id_index ON public.marketing_logs USING btree (campaign_rule_id);


--
-- Name: marketing_logs_campaign_rule_id_user_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX marketing_logs_campaign_rule_id_user_id_status_index ON public.marketing_logs USING btree (campaign_rule_id, user_id, status);


--
-- Name: marketing_logs_provider_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX marketing_logs_provider_id_index ON public.marketing_logs USING btree (provider_id);


--
-- Name: marketing_logs_provider_id_sent_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX marketing_logs_provider_id_sent_at_index ON public.marketing_logs USING btree (provider_id, sent_at);


--
-- Name: marketing_logs_sent_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX marketing_logs_sent_at_index ON public.marketing_logs USING btree (sent_at);


--
-- Name: marketing_logs_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX marketing_logs_user_id_index ON public.marketing_logs USING btree (user_id);


--
-- Name: order_item_options_order_item_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX order_item_options_order_item_id_index ON public.order_item_options USING btree (order_item_id);


--
-- Name: order_items_order_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX order_items_order_id_index ON public.order_items USING btree (order_id);


--
-- Name: order_items_product_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX order_items_product_id_index ON public.order_items USING btree (product_id);


--
-- Name: order_items_variant_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX order_items_variant_id_index ON public.order_items USING btree (variant_id);


--
-- Name: order_ratings_ratee_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX order_ratings_ratee_id_index ON public.order_ratings USING btree (ratee_id);


--
-- Name: order_ratings_reviewer_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX order_ratings_reviewer_id_index ON public.order_ratings USING btree (reviewer_id);


--
-- Name: orders_address_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX orders_address_id_index ON public.orders USING btree (address_id);


--
-- Name: orders_customer_subscription_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX orders_customer_subscription_id_index ON public.orders USING btree (customer_subscription_id);


--
-- Name: orders_delivery_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX orders_delivery_status_index ON public.orders USING btree (delivery_status);


--
-- Name: orders_driver_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX orders_driver_id_index ON public.orders USING btree (driver_id);


--
-- Name: orders_provider_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX orders_provider_id_status_index ON public.orders USING btree (provider_id, status);


--
-- Name: orders_user_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX orders_user_id_status_index ON public.orders USING btree (user_id, status);


--
-- Name: password_reset_requests_user_id_used_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX password_reset_requests_user_id_used_at_index ON public.password_reset_requests USING btree (user_id, used_at);


--
-- Name: payments_order_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX payments_order_id_status_index ON public.payments USING btree (order_id, status);


--
-- Name: payments_transaction_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX payments_transaction_id_index ON public.payments USING btree (transaction_id);


--
-- Name: personal_access_tokens_expires_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX personal_access_tokens_expires_at_index ON public.personal_access_tokens USING btree (expires_at);


--
-- Name: personal_access_tokens_tokenable_type_tokenable_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX personal_access_tokens_tokenable_type_tokenable_id_index ON public.personal_access_tokens USING btree (tokenable_type, tokenable_id);


--
-- Name: product_inventory_product_id_provider_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX product_inventory_product_id_provider_id_index ON public.product_inventory USING btree (product_id, provider_id);


--
-- Name: product_inventory_provider_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX product_inventory_provider_id_index ON public.product_inventory USING btree (provider_id);


--
-- Name: product_option_groups_product_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX product_option_groups_product_id_index ON public.product_option_groups USING btree (product_id);


--
-- Name: product_options_option_group_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX product_options_option_group_id_index ON public.product_options USING btree (option_group_id);


--
-- Name: product_recipes_supply_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX product_recipes_supply_id_index ON public.product_recipes USING btree (supply_id);


--
-- Name: product_variants_product_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX product_variants_product_id_index ON public.product_variants USING btree (product_id);


--
-- Name: products_provider_id_category_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX products_provider_id_category_id_index ON public.products USING btree (provider_id, category_id);


--
-- Name: provider_images_provider_id_image_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX provider_images_provider_id_image_type_index ON public.provider_images USING btree (provider_id, image_type);


--
-- Name: provider_images_provider_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX provider_images_provider_id_index ON public.provider_images USING btree (provider_id);


--
-- Name: provider_services_provider_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX provider_services_provider_id_index ON public.provider_services USING btree (provider_id);


--
-- Name: providers_category_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX providers_category_id_index ON public.providers USING btree (category_id);


--
-- Name: providers_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX providers_is_active_index ON public.providers USING btree (is_active);


--
-- Name: push_tokens_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX push_tokens_user_id_index ON public.push_tokens USING btree (user_id);


--
-- Name: regions_country_id_parent_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX regions_country_id_parent_id_index ON public.regions USING btree (country_id, parent_id);


--
-- Name: regions_country_id_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX regions_country_id_type_index ON public.regions USING btree (country_id, type);


--
-- Name: service_orders_customer_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX service_orders_customer_id_status_index ON public.service_orders USING btree (customer_id, status);


--
-- Name: service_orders_escrow_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX service_orders_escrow_status_index ON public.service_orders USING btree (escrow_status);


--
-- Name: service_orders_merchant_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX service_orders_merchant_id_status_index ON public.service_orders USING btree (merchant_id, status);


--
-- Name: service_orders_order_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX service_orders_order_type_index ON public.service_orders USING btree (order_type);


--
-- Name: service_orders_scheduled_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX service_orders_scheduled_at_index ON public.service_orders USING btree (scheduled_at);


--
-- Name: service_orders_sub_group_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX service_orders_sub_group_id_index ON public.service_orders USING btree (sub_group_id);


--
-- Name: service_quotes_provider_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX service_quotes_provider_id_index ON public.service_quotes USING btree (provider_id);


--
-- Name: service_quotes_service_order_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX service_quotes_service_order_id_index ON public.service_quotes USING btree (service_order_id);


--
-- Name: service_quotes_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX service_quotes_status_index ON public.service_quotes USING btree (status);


--
-- Name: service_ratings_ratee_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX service_ratings_ratee_id_index ON public.service_ratings USING btree (ratee_id);


--
-- Name: service_ratings_reviewer_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX service_ratings_reviewer_id_index ON public.service_ratings USING btree (reviewer_id);


--
-- Name: sessions_last_activity_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_last_activity_index ON public.sessions USING btree (last_activity);


--
-- Name: sessions_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_user_id_index ON public.sessions USING btree (user_id);


--
-- Name: subscription_payments_customer_subscription_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX subscription_payments_customer_subscription_id_status_index ON public.subscription_payments USING btree (customer_subscription_id, status);


--
-- Name: subscription_payments_order_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX subscription_payments_order_id_index ON public.subscription_payments USING btree (order_id);


--
-- Name: subscription_payments_period_start_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX subscription_payments_period_start_index ON public.subscription_payments USING btree (period_start);


--
-- Name: subscription_payments_provider_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX subscription_payments_provider_id_status_index ON public.subscription_payments USING btree (provider_id, status);


--
-- Name: subscription_plan_items_product_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX subscription_plan_items_product_id_index ON public.subscription_plan_items USING btree (product_id);


--
-- Name: subscription_plans_provider_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX subscription_plans_provider_id_index ON public.subscription_plans USING btree (provider_id);


--
-- Name: subscription_plans_provider_id_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX subscription_plans_provider_id_is_active_index ON public.subscription_plans USING btree (provider_id, is_active);


--
-- Name: supplies_unit_of_measure_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX supplies_unit_of_measure_id_index ON public.supplies USING btree (unit_of_measure_id);


--
-- Name: user_notifications_user_id_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX user_notifications_user_id_created_at_index ON public.user_notifications USING btree (user_id, created_at);


--
-- Name: user_notifications_user_id_read_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX user_notifications_user_id_read_at_index ON public.user_notifications USING btree (user_id, read_at);


--
-- Name: wallet_transactions_order_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX wallet_transactions_order_id_index ON public.wallet_transactions USING btree (order_id);


--
-- Name: wallet_transactions_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX wallet_transactions_user_id_index ON public.wallet_transactions USING btree (user_id);


--
-- Name: addresses addresses_country_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.addresses
    ADD CONSTRAINT addresses_country_id_foreign FOREIGN KEY (country_id) REFERENCES public.countries(id);


--
-- Name: addresses addresses_department_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.addresses
    ADD CONSTRAINT addresses_department_id_foreign FOREIGN KEY (department_id) REFERENCES public.regions(id) ON DELETE SET NULL;


--
-- Name: addresses addresses_province_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.addresses
    ADD CONSTRAINT addresses_province_id_foreign FOREIGN KEY (province_id) REFERENCES public.regions(id) ON DELETE SET NULL;


--
-- Name: addresses addresses_region_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.addresses
    ADD CONSTRAINT addresses_region_id_foreign FOREIGN KEY (region_id) REFERENCES public.regions(id) ON DELETE SET NULL;


--
-- Name: addresses addresses_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.addresses
    ADD CONSTRAINT addresses_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: categories categories_provider_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.categories
    ADD CONSTRAINT categories_provider_id_foreign FOREIGN KEY (provider_id) REFERENCES public.providers(id) ON DELETE CASCADE;


--
-- Name: currencies currencies_country_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.currencies
    ADD CONSTRAINT currencies_country_id_foreign FOREIGN KEY (country_id) REFERENCES public.countries(id) ON DELETE CASCADE;


--
-- Name: customer_subscription_items customer_subscription_items_customer_subscription_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.customer_subscription_items
    ADD CONSTRAINT customer_subscription_items_customer_subscription_id_foreign FOREIGN KEY (customer_subscription_id) REFERENCES public.customer_subscriptions(id) ON DELETE CASCADE;


--
-- Name: customer_subscription_items customer_subscription_items_product_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.customer_subscription_items
    ADD CONSTRAINT customer_subscription_items_product_id_foreign FOREIGN KEY (product_id) REFERENCES public.products(id) ON DELETE CASCADE;


--
-- Name: customer_subscriptions customer_subscriptions_delivery_address_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.customer_subscriptions
    ADD CONSTRAINT customer_subscriptions_delivery_address_id_foreign FOREIGN KEY (delivery_address_id) REFERENCES public.addresses(id) ON DELETE RESTRICT;


--
-- Name: customer_subscriptions customer_subscriptions_provider_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.customer_subscriptions
    ADD CONSTRAINT customer_subscriptions_provider_id_foreign FOREIGN KEY (provider_id) REFERENCES public.providers(id) ON DELETE CASCADE;


--
-- Name: customer_subscriptions customer_subscriptions_subscription_plan_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.customer_subscriptions
    ADD CONSTRAINT customer_subscriptions_subscription_plan_id_foreign FOREIGN KEY (subscription_plan_id) REFERENCES public.subscription_plans(id) ON DELETE RESTRICT;


--
-- Name: customer_subscriptions customer_subscriptions_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.customer_subscriptions
    ADD CONSTRAINT customer_subscriptions_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: driver_wallets driver_wallets_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.driver_wallets
    ADD CONSTRAINT driver_wallets_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: driver_withdrawal_requests driver_withdrawal_requests_reviewed_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.driver_withdrawal_requests
    ADD CONSTRAINT driver_withdrawal_requests_reviewed_by_foreign FOREIGN KEY (reviewed_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: driver_withdrawal_requests driver_withdrawal_requests_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.driver_withdrawal_requests
    ADD CONSTRAINT driver_withdrawal_requests_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: driver_withdrawal_requests driver_withdrawal_requests_wallet_transaction_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.driver_withdrawal_requests
    ADD CONSTRAINT driver_withdrawal_requests_wallet_transaction_id_foreign FOREIGN KEY (wallet_transaction_id) REFERENCES public.wallet_transactions(id) ON DELETE SET NULL;


--
-- Name: favorites favorites_provider_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.favorites
    ADD CONSTRAINT favorites_provider_id_foreign FOREIGN KEY (provider_id) REFERENCES public.providers(id) ON DELETE CASCADE;


--
-- Name: favorites favorites_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.favorites
    ADD CONSTRAINT favorites_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: groups groups_group_status_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.groups
    ADD CONSTRAINT groups_group_status_id_foreign FOREIGN KEY (group_status_id) REFERENCES public.group_statuses(id) ON DELETE SET NULL;


--
-- Name: groups groups_module_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.groups
    ADD CONSTRAINT groups_module_id_foreign FOREIGN KEY (module_id) REFERENCES public.modules(id) ON DELETE SET NULL;


--
-- Name: inventory_movements inventory_movements_order_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.inventory_movements
    ADD CONSTRAINT inventory_movements_order_id_foreign FOREIGN KEY (order_id) REFERENCES public.orders(id) ON DELETE SET NULL;


--
-- Name: inventory_movements inventory_movements_product_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.inventory_movements
    ADD CONSTRAINT inventory_movements_product_id_foreign FOREIGN KEY (product_id) REFERENCES public.products(id) ON DELETE CASCADE;


--
-- Name: inventory_movements inventory_movements_provider_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.inventory_movements
    ADD CONSTRAINT inventory_movements_provider_id_foreign FOREIGN KEY (provider_id) REFERENCES public.providers(id) ON DELETE CASCADE;


--
-- Name: inventory_movements inventory_movements_unit_of_measure_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.inventory_movements
    ADD CONSTRAINT inventory_movements_unit_of_measure_id_foreign FOREIGN KEY (unit_of_measure_id) REFERENCES public.unit_of_measures(id) ON DELETE RESTRICT;


--
-- Name: marketing_campaign_rules marketing_campaign_rules_provider_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.marketing_campaign_rules
    ADD CONSTRAINT marketing_campaign_rules_provider_id_foreign FOREIGN KEY (provider_id) REFERENCES public.providers(id) ON DELETE CASCADE;


--
-- Name: marketing_logs marketing_logs_campaign_rule_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.marketing_logs
    ADD CONSTRAINT marketing_logs_campaign_rule_id_foreign FOREIGN KEY (campaign_rule_id) REFERENCES public.marketing_campaign_rules(id) ON DELETE CASCADE;


--
-- Name: marketing_logs marketing_logs_provider_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.marketing_logs
    ADD CONSTRAINT marketing_logs_provider_id_foreign FOREIGN KEY (provider_id) REFERENCES public.providers(id) ON DELETE CASCADE;


--
-- Name: marketing_logs marketing_logs_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.marketing_logs
    ADD CONSTRAINT marketing_logs_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: order_item_options order_item_options_order_item_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.order_item_options
    ADD CONSTRAINT order_item_options_order_item_id_foreign FOREIGN KEY (order_item_id) REFERENCES public.order_items(id) ON DELETE CASCADE;


--
-- Name: order_items order_items_order_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.order_items
    ADD CONSTRAINT order_items_order_id_foreign FOREIGN KEY (order_id) REFERENCES public.orders(id) ON DELETE CASCADE;


--
-- Name: order_items order_items_product_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.order_items
    ADD CONSTRAINT order_items_product_id_foreign FOREIGN KEY (product_id) REFERENCES public.products(id) ON DELETE SET NULL;


--
-- Name: order_items order_items_variant_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.order_items
    ADD CONSTRAINT order_items_variant_id_foreign FOREIGN KEY (variant_id) REFERENCES public.product_variants(id) ON DELETE SET NULL;


--
-- Name: order_ratings order_ratings_order_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.order_ratings
    ADD CONSTRAINT order_ratings_order_id_foreign FOREIGN KEY (order_id) REFERENCES public.orders(id) ON DELETE CASCADE;


--
-- Name: order_ratings order_ratings_ratee_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.order_ratings
    ADD CONSTRAINT order_ratings_ratee_id_foreign FOREIGN KEY (ratee_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: order_ratings order_ratings_reviewer_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.order_ratings
    ADD CONSTRAINT order_ratings_reviewer_id_foreign FOREIGN KEY (reviewer_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: orders orders_address_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.orders
    ADD CONSTRAINT orders_address_id_foreign FOREIGN KEY (address_id) REFERENCES public.addresses(id) ON DELETE SET NULL;


--
-- Name: orders orders_customer_subscription_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.orders
    ADD CONSTRAINT orders_customer_subscription_id_foreign FOREIGN KEY (customer_subscription_id) REFERENCES public.customer_subscriptions(id) ON DELETE SET NULL;


--
-- Name: orders orders_provider_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.orders
    ADD CONSTRAINT orders_provider_id_foreign FOREIGN KEY (provider_id) REFERENCES public.providers(id) ON DELETE CASCADE;


--
-- Name: orders orders_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.orders
    ADD CONSTRAINT orders_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: page_user page_user_page_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.page_user
    ADD CONSTRAINT page_user_page_id_fkey FOREIGN KEY (page_id) REFERENCES public.pages(id) ON DELETE CASCADE;


--
-- Name: page_user page_user_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.page_user
    ADD CONSTRAINT page_user_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: pages pages_module_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pages
    ADD CONSTRAINT pages_module_id_fkey FOREIGN KEY (module_id) REFERENCES public.modules(id) ON DELETE CASCADE;


--
-- Name: page_type_user paginasptiposusuarios_page_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.page_type_user
    ADD CONSTRAINT paginasptiposusuarios_page_id_foreign FOREIGN KEY (page_id) REFERENCES public.pages(id) ON DELETE CASCADE;


--
-- Name: page_type_user paginasptiposusuarios_type_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.page_type_user
    ADD CONSTRAINT paginasptiposusuarios_type_user_id_foreign FOREIGN KEY (type_user_id) REFERENCES public.type_users(id) ON DELETE CASCADE;


--
-- Name: password_reset_requests password_reset_requests_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.password_reset_requests
    ADD CONSTRAINT password_reset_requests_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: payments payments_order_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payments
    ADD CONSTRAINT payments_order_id_foreign FOREIGN KEY (order_id) REFERENCES public.orders(id) ON DELETE CASCADE;


--
-- Name: product_inventory product_inventory_product_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_inventory
    ADD CONSTRAINT product_inventory_product_id_foreign FOREIGN KEY (product_id) REFERENCES public.products(id) ON DELETE CASCADE;


--
-- Name: product_inventory product_inventory_provider_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_inventory
    ADD CONSTRAINT product_inventory_provider_id_foreign FOREIGN KEY (provider_id) REFERENCES public.providers(id) ON DELETE CASCADE;


--
-- Name: product_option_groups product_option_groups_product_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_option_groups
    ADD CONSTRAINT product_option_groups_product_id_foreign FOREIGN KEY (product_id) REFERENCES public.products(id) ON DELETE CASCADE;


--
-- Name: product_options product_options_option_group_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_options
    ADD CONSTRAINT product_options_option_group_id_foreign FOREIGN KEY (option_group_id) REFERENCES public.product_option_groups(id) ON DELETE CASCADE;


--
-- Name: product_recipes product_recipes_product_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_recipes
    ADD CONSTRAINT product_recipes_product_id_foreign FOREIGN KEY (product_id) REFERENCES public.products(id) ON DELETE CASCADE;


--
-- Name: product_recipes product_recipes_supply_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_recipes
    ADD CONSTRAINT product_recipes_supply_id_foreign FOREIGN KEY (supply_id) REFERENCES public.supplies(id) ON DELETE CASCADE;


--
-- Name: product_variants product_variants_product_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_variants
    ADD CONSTRAINT product_variants_product_id_foreign FOREIGN KEY (product_id) REFERENCES public.products(id) ON DELETE CASCADE;


--
-- Name: products products_category_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.products
    ADD CONSTRAINT products_category_id_foreign FOREIGN KEY (category_id) REFERENCES public.categories(id) ON DELETE CASCADE;


--
-- Name: products products_provider_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.products
    ADD CONSTRAINT products_provider_id_foreign FOREIGN KEY (provider_id) REFERENCES public.providers(id) ON DELETE CASCADE;


--
-- Name: products products_unit_of_measure_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.products
    ADD CONSTRAINT products_unit_of_measure_id_foreign FOREIGN KEY (unit_of_measure_id) REFERENCES public.unit_of_measures(id) ON DELETE SET NULL;


--
-- Name: provider_drivers provider_drivers_provider_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.provider_drivers
    ADD CONSTRAINT provider_drivers_provider_id_foreign FOREIGN KEY (provider_id) REFERENCES public.providers(id) ON DELETE CASCADE;


--
-- Name: provider_drivers provider_drivers_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.provider_drivers
    ADD CONSTRAINT provider_drivers_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: provider_images provider_images_provider_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.provider_images
    ADD CONSTRAINT provider_images_provider_id_foreign FOREIGN KEY (provider_id) REFERENCES public.providers(id) ON DELETE CASCADE;


--
-- Name: provider_service_types provider_service_types_provider_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.provider_service_types
    ADD CONSTRAINT provider_service_types_provider_id_foreign FOREIGN KEY (provider_id) REFERENCES public.providers(id) ON DELETE CASCADE;


--
-- Name: provider_service_types provider_service_types_service_type_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.provider_service_types
    ADD CONSTRAINT provider_service_types_service_type_id_foreign FOREIGN KEY (service_type_id) REFERENCES public.service_types(id) ON DELETE CASCADE;


--
-- Name: provider_services provider_services_provider_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.provider_services
    ADD CONSTRAINT provider_services_provider_id_foreign FOREIGN KEY (provider_id) REFERENCES public.providers(id) ON DELETE CASCADE;


--
-- Name: provider_subgroups provider_subgroups_provider_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.provider_subgroups
    ADD CONSTRAINT provider_subgroups_provider_id_foreign FOREIGN KEY (provider_id) REFERENCES public.providers(id) ON DELETE CASCADE;


--
-- Name: provider_subgroups provider_subgroups_subgroup_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.provider_subgroups
    ADD CONSTRAINT provider_subgroups_subgroup_id_foreign FOREIGN KEY (subgroup_id) REFERENCES public.sub_groups(id) ON DELETE CASCADE;


--
-- Name: providers providers_category_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.providers
    ADD CONSTRAINT providers_category_id_foreign FOREIGN KEY (category_id) REFERENCES public.groups(id) ON DELETE SET NULL;


--
-- Name: providers providers_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.providers
    ADD CONSTRAINT providers_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: push_tokens push_tokens_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.push_tokens
    ADD CONSTRAINT push_tokens_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: regions regions_country_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.regions
    ADD CONSTRAINT regions_country_id_foreign FOREIGN KEY (country_id) REFERENCES public.countries(id) ON DELETE CASCADE;


--
-- Name: regions regions_parent_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.regions
    ADD CONSTRAINT regions_parent_id_foreign FOREIGN KEY (parent_id) REFERENCES public.regions(id) ON DELETE CASCADE;


--
-- Name: service_orders service_orders_customer_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.service_orders
    ADD CONSTRAINT service_orders_customer_id_foreign FOREIGN KEY (customer_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: service_orders service_orders_merchant_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.service_orders
    ADD CONSTRAINT service_orders_merchant_id_foreign FOREIGN KEY (merchant_id) REFERENCES public.providers(id) ON DELETE SET NULL;


--
-- Name: service_orders service_orders_sub_group_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.service_orders
    ADD CONSTRAINT service_orders_sub_group_id_foreign FOREIGN KEY (sub_group_id) REFERENCES public.sub_groups(id) ON DELETE CASCADE;


--
-- Name: service_quotes service_quotes_provider_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.service_quotes
    ADD CONSTRAINT service_quotes_provider_id_foreign FOREIGN KEY (provider_id) REFERENCES public.providers(id) ON DELETE CASCADE;


--
-- Name: service_quotes service_quotes_service_order_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.service_quotes
    ADD CONSTRAINT service_quotes_service_order_id_foreign FOREIGN KEY (service_order_id) REFERENCES public.service_orders(id) ON DELETE CASCADE;


--
-- Name: service_ratings service_ratings_ratee_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.service_ratings
    ADD CONSTRAINT service_ratings_ratee_id_foreign FOREIGN KEY (ratee_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: service_ratings service_ratings_reviewer_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.service_ratings
    ADD CONSTRAINT service_ratings_reviewer_id_foreign FOREIGN KEY (reviewer_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: service_ratings service_ratings_service_order_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.service_ratings
    ADD CONSTRAINT service_ratings_service_order_id_foreign FOREIGN KEY (service_order_id) REFERENCES public.service_orders(id) ON DELETE CASCADE;


--
-- Name: sub_groups sub_groups_group_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sub_groups
    ADD CONSTRAINT sub_groups_group_id_foreign FOREIGN KEY (group_id) REFERENCES public.groups(id) ON DELETE CASCADE;


--
-- Name: subscription_payments subscription_payments_customer_subscription_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.subscription_payments
    ADD CONSTRAINT subscription_payments_customer_subscription_id_foreign FOREIGN KEY (customer_subscription_id) REFERENCES public.customer_subscriptions(id) ON DELETE RESTRICT;


--
-- Name: subscription_payments subscription_payments_order_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.subscription_payments
    ADD CONSTRAINT subscription_payments_order_id_foreign FOREIGN KEY (order_id) REFERENCES public.orders(id) ON DELETE SET NULL;


--
-- Name: subscription_payments subscription_payments_provider_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.subscription_payments
    ADD CONSTRAINT subscription_payments_provider_id_foreign FOREIGN KEY (provider_id) REFERENCES public.providers(id) ON DELETE CASCADE;


--
-- Name: subscription_payments subscription_payments_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.subscription_payments
    ADD CONSTRAINT subscription_payments_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: subscription_plan_items subscription_plan_items_product_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.subscription_plan_items
    ADD CONSTRAINT subscription_plan_items_product_id_foreign FOREIGN KEY (product_id) REFERENCES public.products(id) ON DELETE CASCADE;


--
-- Name: subscription_plan_items subscription_plan_items_subscription_plan_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.subscription_plan_items
    ADD CONSTRAINT subscription_plan_items_subscription_plan_id_foreign FOREIGN KEY (subscription_plan_id) REFERENCES public.subscription_plans(id) ON DELETE CASCADE;


--
-- Name: subscription_plans subscription_plans_provider_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.subscription_plans
    ADD CONSTRAINT subscription_plans_provider_id_foreign FOREIGN KEY (provider_id) REFERENCES public.providers(id) ON DELETE CASCADE;


--
-- Name: supplies supplies_provider_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.supplies
    ADD CONSTRAINT supplies_provider_id_foreign FOREIGN KEY (provider_id) REFERENCES public.providers(id) ON DELETE CASCADE;


--
-- Name: supplies supplies_unit_of_measure_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.supplies
    ADD CONSTRAINT supplies_unit_of_measure_id_foreign FOREIGN KEY (unit_of_measure_id) REFERENCES public.unit_of_measures(id) ON DELETE RESTRICT;


--
-- Name: user_notifications user_notifications_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_notifications
    ADD CONSTRAINT user_notifications_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: users users_type_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_type_user_id_fkey FOREIGN KEY (type_user_id) REFERENCES public.type_users(id) ON DELETE SET NULL;


--
-- Name: users users_user_status_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_user_status_id_foreign FOREIGN KEY (user_status_id) REFERENCES public.user_statuses(id) ON DELETE SET NULL;


--
-- Name: wallet_transactions wallet_transactions_order_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.wallet_transactions
    ADD CONSTRAINT wallet_transactions_order_id_foreign FOREIGN KEY (order_id) REFERENCES public.orders(id) ON DELETE SET NULL;


--
-- Name: wallet_transactions wallet_transactions_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.wallet_transactions
    ADD CONSTRAINT wallet_transactions_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- PostgreSQL database dump complete
--

\unrestrict 0pRbdfLKhuns0eYExvIQNV1EQT4oGOTlcxnerdhmtTCYGLWc3P03X0NQyhuwm3B

