--
-- PostgreSQL database dump
--

\restrict YcIdgE4b0vwdBVYi8ZAOcpZsKQsavwZFgmC21wAWhneVghzc3MfSMccfBnpUgF9

-- Dumped from database version 17.11
-- Dumped by pg_dump version 17.11

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
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
-- Name: audit_logs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.audit_logs (
    id bigint NOT NULL,
    causer_type character varying(255) NOT NULL,
    causer_id bigint NOT NULL,
    action character varying(20) NOT NULL,
    subject_type character varying(255) NOT NULL,
    subject_id bigint NOT NULL,
    description text,
    ip_address character varying(45),
    user_agent text,
    changes json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: audit_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.audit_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: audit_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.audit_logs_id_seq OWNED BY public.audit_logs.id;


--
-- Name: barangs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.barangs (
    id bigint NOT NULL,
    gudang_id bigint,
    kategori_id bigint,
    satuan_id bigint,
    kode character varying(50) NOT NULL,
    nama character varying(255) NOT NULL,
    minimum_stok integer DEFAULT 0 NOT NULL,
    harga_satuan numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    deskripsi text,
    is_aktif boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    foto_path character varying(255),
    lokasi_rak character varying(100),
    master_barang_id bigint
);


--
-- Name: barangs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.barangs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: barangs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.barangs_id_seq OWNED BY public.barangs.id;


--
-- Name: cache; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cache (
    key character varying(255) NOT NULL,
    value text NOT NULL,
    expiration bigint NOT NULL
);


--
-- Name: cache_locks; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cache_locks (
    key character varying(255) NOT NULL,
    owner character varying(255) NOT NULL,
    expiration bigint NOT NULL
);


--
-- Name: divisi_pemintas; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.divisi_pemintas (
    id bigint NOT NULL,
    gudang_id bigint NOT NULL,
    kode character varying(30),
    nama character varying(255) NOT NULL,
    penanggung_jawab character varying(100),
    is_aktif boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: divisi_pemintas_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.divisi_pemintas_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: divisi_pemintas_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.divisi_pemintas_id_seq OWNED BY public.divisi_pemintas.id;


--
-- Name: failed_jobs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.failed_jobs (
    id bigint NOT NULL,
    uuid character varying(255) NOT NULL,
    connection character varying(255) NOT NULL,
    queue character varying(255) NOT NULL,
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
-- Name: gudangs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.gudangs (
    id bigint NOT NULL,
    kode character varying(50) NOT NULL,
    nama character varying(255) NOT NULL,
    jenis character varying(255) DEFAULT 'gudang_sukahati'::character varying NOT NULL,
    is_aktif boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: gudangs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.gudangs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: gudangs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.gudangs_id_seq OWNED BY public.gudangs.id;


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
-- Name: kategori_barangs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.kategori_barangs (
    id bigint NOT NULL,
    gudang_id bigint,
    nama character varying(100) NOT NULL,
    is_aktif boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    deskripsi character varying(200)
);


--
-- Name: kategori_barangs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.kategori_barangs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: kategori_barangs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.kategori_barangs_id_seq OWNED BY public.kategori_barangs.id;


--
-- Name: kwh_catatans; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.kwh_catatans (
    id bigint NOT NULL,
    gudang_id bigint NOT NULL,
    tanggal date NOT NULL,
    lwbp_meter numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    wbp_meter numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    selisih_lwbp numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    selisih_wbp numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    ct_factor numeric(8,2) DEFAULT '60'::numeric NOT NULL,
    tarif_lwbp numeric(10,2) DEFAULT '972'::numeric NOT NULL,
    tarif_wbp numeric(10,2) DEFAULT '1458'::numeric NOT NULL,
    nominal_lwbp numeric(14,2) DEFAULT '0'::numeric NOT NULL,
    nominal_wbp numeric(14,2) DEFAULT '0'::numeric NOT NULL,
    total_biaya numeric(14,2) DEFAULT '0'::numeric NOT NULL,
    keterangan character varying(255),
    pencatat_id bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: kwh_catatans_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.kwh_catatans_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: kwh_catatans_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.kwh_catatans_id_seq OWNED BY public.kwh_catatans.id;


--
-- Name: master_barangs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.master_barangs (
    id bigint NOT NULL,
    kode character varying(50),
    nama character varying(255) NOT NULL,
    kategori_id bigint,
    satuan_id bigint,
    harga_satuan numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    deskripsi text,
    foto_path character varying(255),
    is_aktif boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


--
-- Name: master_barangs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.master_barangs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: master_barangs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.master_barangs_id_seq OWNED BY public.master_barangs.id;


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
-- Name: model_has_permissions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.model_has_permissions (
    permission_id bigint NOT NULL,
    model_type character varying(255) NOT NULL,
    model_id bigint NOT NULL
);


--
-- Name: model_has_roles; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.model_has_roles (
    role_id bigint NOT NULL,
    model_type character varying(255) NOT NULL,
    model_id bigint NOT NULL
);


--
-- Name: password_reset_tokens; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.password_reset_tokens (
    username character varying(255) NOT NULL,
    token character varying(255) NOT NULL,
    created_at timestamp(0) without time zone
);


--
-- Name: penerimaan_barang_store_details; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.penerimaan_barang_store_details (
    id bigint NOT NULL,
    penerimaan_barang_store_id bigint NOT NULL,
    barang_id bigint NOT NULL,
    qty_diterima integer NOT NULL,
    satuan_id bigint,
    kondisi character varying(50) DEFAULT 'Baik'::character varying NOT NULL,
    catatan text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    qty_rusak integer DEFAULT 0 NOT NULL,
    qty_kurang integer DEFAULT 0 NOT NULL,
    harga_satuan numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    total_harga numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    user_id bigint,
    pemakai character varying(255),
    qty_lebih integer DEFAULT 0 NOT NULL
);


--
-- Name: penerimaan_barang_store_details_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.penerimaan_barang_store_details_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: penerimaan_barang_store_details_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.penerimaan_barang_store_details_id_seq OWNED BY public.penerimaan_barang_store_details.id;


--
-- Name: penerimaan_barang_stores; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.penerimaan_barang_stores (
    id bigint NOT NULL,
    nomor_penerimaan character varying(255) NOT NULL,
    gudang_id bigint NOT NULL,
    barang_id bigint,
    qty_diterima integer DEFAULT 0 NOT NULL,
    satuan_id bigint,
    po_referensi character varying(255),
    sumber_supplier character varying(255),
    foto_dokumen_path character varying(255),
    tanpa_surat_jalan boolean DEFAULT false NOT NULL,
    alasan_tanpa_sj text,
    diterima_oleh bigint,
    tanggal_diterima timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    catatan text,
    pemesan character varying(255),
    pengambil_barang character varying(255),
    user_id bigint,
    metode_pembayaran character varying(30) DEFAULT 'Tunai'::character varying NOT NULL,
    catatan_rekap text,
    pemakai character varying(255),
    source_groups json,
    follow_up_completed_at timestamp(0) without time zone,
    follow_up_completed_by bigint,
    follow_up_note text
);


--
-- Name: penerimaan_barang_stores_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.penerimaan_barang_stores_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: penerimaan_barang_stores_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.penerimaan_barang_stores_id_seq OWNED BY public.penerimaan_barang_stores.id;


--
-- Name: penerimaan_pemindahan_units; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.penerimaan_pemindahan_units (
    id bigint NOT NULL,
    transfer_barang_id bigint NOT NULL,
    gudang_id bigint NOT NULL,
    barang_id bigint NOT NULL,
    qty_dikirim integer NOT NULL,
    qty_diterima integer NOT NULL,
    qty_rusak integer DEFAULT 0 NOT NULL,
    catatan_discrepancy text,
    diterima_oleh bigint,
    diterima_at timestamp(0) without time zone NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    qty_diperiksa numeric(14,3),
    qty_rusak_diperiksa numeric(14,3),
    status_verifikasi character varying(30) DEFAULT 'menunggu_verifikasi'::character varying NOT NULL,
    diverifikasi_oleh bigint,
    diverifikasi_at timestamp(0) without time zone,
    catatan_verifikasi text
);


--
-- Name: penerimaan_pemindahan_units_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.penerimaan_pemindahan_units_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: penerimaan_pemindahan_units_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.penerimaan_pemindahan_units_id_seq OWNED BY public.penerimaan_pemindahan_units.id;


--
-- Name: penerimaan_unit_details; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.penerimaan_unit_details (
    id bigint NOT NULL,
    penerimaan_unit_id bigint NOT NULL,
    barang_id bigint,
    kode_barang character varying(255),
    nama_barang character varying(255) NOT NULL,
    kategori character varying(255),
    satuan character varying(50) NOT NULL,
    qty_diterima numeric(14,3) NOT NULL,
    kondisi character varying(50) DEFAULT 'Baik'::character varying NOT NULL,
    qty_rusak numeric(14,3) DEFAULT '0'::numeric NOT NULL,
    qty_kurang numeric(14,3) DEFAULT '0'::numeric NOT NULL,
    qty_lebih numeric(14,3) DEFAULT '0'::numeric NOT NULL,
    catatan text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    harga_beli numeric(14,2) DEFAULT '0'::numeric NOT NULL,
    subtotal_nilai numeric(14,2) DEFAULT '0'::numeric NOT NULL,
    qty_diperiksa numeric(14,3),
    qty_rusak_diperiksa numeric(14,3)
);


--
-- Name: penerimaan_unit_details_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.penerimaan_unit_details_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: penerimaan_unit_details_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.penerimaan_unit_details_id_seq OWNED BY public.penerimaan_unit_details.id;


--
-- Name: penerimaan_units; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.penerimaan_units (
    id bigint NOT NULL,
    nomor_rcv character varying(255) NOT NULL,
    gudang_id bigint NOT NULL,
    nama_supplier character varying(255) NOT NULL,
    tanggal_terima date NOT NULL,
    waktu_terima time(0) without time zone NOT NULL,
    po_referensi character varying(255),
    nomor_surat_jalan character varying(255),
    has_surat_jalan boolean DEFAULT true NOT NULL,
    alasan_tanpa_sj text,
    foto_surat_jalan_path character varying(255),
    catatan text,
    diterima_oleh bigint,
    status_verifikasi character varying(30) DEFAULT 'tercatat'::character varying NOT NULL,
    diverifikasi_oleh bigint,
    diverifikasi_at timestamp(0) without time zone,
    catatan_verifikasi text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: penerimaan_units_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.penerimaan_units_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: penerimaan_units_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.penerimaan_units_id_seq OWNED BY public.penerimaan_units.id;


--
-- Name: pengeluaran_unit_details; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.pengeluaran_unit_details (
    id bigint NOT NULL,
    pengeluaran_unit_id bigint NOT NULL,
    barang_id bigint,
    kode_barang character varying(255),
    nama_barang character varying(255) NOT NULL,
    kategori character varying(255),
    satuan character varying(50) NOT NULL,
    qty numeric(14,3) NOT NULL,
    catatan text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    harga_hpp numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    subtotal_nilai numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    qty_kembali numeric(14,3) DEFAULT '0'::numeric NOT NULL,
    qty_kembali_rekonsiliasi numeric(14,3),
    qty_keluar_dilaporkan numeric(14,3),
    qty_keluar_rekonsiliasi numeric(14,3)
);


--
-- Name: pengeluaran_unit_details_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.pengeluaran_unit_details_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: pengeluaran_unit_details_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.pengeluaran_unit_details_id_seq OWNED BY public.pengeluaran_unit_details.id;


--
-- Name: pengeluaran_unit_number_migration_backups; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.pengeluaran_unit_number_migration_backups (
    pengeluaran_unit_id bigint NOT NULL,
    nomor_bpb_lama character varying(255) NOT NULL
);


--
-- Name: pengeluaran_units; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.pengeluaran_units (
    id bigint NOT NULL,
    nomor_bpb character varying(255) NOT NULL,
    gudang_id bigint NOT NULL,
    tanggal date NOT NULL,
    waktu_keluar time(0) without time zone NOT NULL,
    bagian_peminta character varying(255) NOT NULL,
    catatan text,
    entry_mode character varying(20) DEFAULT 'live'::character varying NOT NULL,
    status_verifikasi character varying(30) DEFAULT 'menunggu_verifikasi'::character varying NOT NULL,
    dicatat_oleh bigint,
    diverifikasi_oleh bigint,
    diverifikasi_at timestamp(0) without time zone,
    catatan_verifikasi text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    divisi_peminta_id bigint
);


--
-- Name: pengeluaran_units_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.pengeluaran_units_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: pengeluaran_units_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.pengeluaran_units_id_seq OWNED BY public.pengeluaran_units.id;


--
-- Name: pengembalian_units; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.pengembalian_units (
    id bigint NOT NULL,
    gudang_id bigint NOT NULL,
    pengeluaran_unit_id bigint,
    pengeluaran_unit_detail_id bigint,
    barang_id bigint,
    bagian_peminta character varying(255) NOT NULL,
    nama_barang character varying(255) NOT NULL,
    satuan character varying(50) DEFAULT 'unit'::character varying NOT NULL,
    qty_kembali numeric(14,3) NOT NULL,
    tanggal_kembali date NOT NULL,
    catatan text,
    dicatat_oleh bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: pengembalian_units_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.pengembalian_units_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: pengembalian_units_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.pengembalian_units_id_seq OWNED BY public.pengembalian_units.id;


--
-- Name: permintaan_barang_details; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.permintaan_barang_details (
    id bigint NOT NULL,
    permintaan_barang_id bigint NOT NULL,
    barang_id bigint NOT NULL,
    qty_diminta integer NOT NULL,
    qty_diproses integer DEFAULT 0 NOT NULL,
    satuan_id bigint,
    catatan text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: permintaan_barang_details_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.permintaan_barang_details_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: permintaan_barang_details_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.permintaan_barang_details_id_seq OWNED BY public.permintaan_barang_details.id;


--
-- Name: permintaan_barangs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.permintaan_barangs (
    id bigint NOT NULL,
    nomor_pr character varying(255) NOT NULL,
    gudang_peminta_id bigint NOT NULL,
    gudang_tujuan_id bigint NOT NULL,
    status character varying(255) DEFAULT 'baru'::character varying NOT NULL,
    catatan text,
    alasan_penolakan text,
    dibuat_oleh bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT permintaan_barangs_status_check CHECK (((status)::text = ANY ((ARRAY['baru'::character varying, 'diproses'::character varying, 'selesai'::character varying, 'ditolak'::character varying])::text[])))
);


--
-- Name: permintaan_barangs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.permintaan_barangs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: permintaan_barangs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.permintaan_barangs_id_seq OWNED BY public.permintaan_barangs.id;


--
-- Name: permissions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.permissions (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    guard_name character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: permissions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.permissions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: permissions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.permissions_id_seq OWNED BY public.permissions.id;


--
-- Name: role_has_permissions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.role_has_permissions (
    permission_id bigint NOT NULL,
    role_id bigint NOT NULL
);


--
-- Name: roles; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.roles (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    guard_name character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    description text
);


--
-- Name: roles_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.roles_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: roles_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.roles_id_seq OWNED BY public.roles.id;


--
-- Name: satuan_konversis; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.satuan_konversis (
    id bigint NOT NULL,
    barang_id bigint NOT NULL,
    satuan_turunan_id bigint NOT NULL,
    rasio_konversi numeric(10,2) DEFAULT '1'::numeric NOT NULL,
    label_konversi character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: satuan_konversis_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.satuan_konversis_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: satuan_konversis_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.satuan_konversis_id_seq OWNED BY public.satuan_konversis.id;


--
-- Name: satuans; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.satuans (
    id bigint NOT NULL,
    kode character varying(20) NOT NULL,
    nama character varying(100) NOT NULL,
    is_aktif boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    gudang_id bigint
);


--
-- Name: satuans_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.satuans_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: satuans_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.satuans_id_seq OWNED BY public.satuans.id;


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
-- Name: stok_awals; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.stok_awals (
    id bigint NOT NULL,
    gudang_id bigint,
    barang_id bigint NOT NULL,
    qty numeric(15,3) NOT NULL,
    tanggal_acuan date NOT NULL,
    catatan text,
    sumber character varying(255) DEFAULT 'manual'::character varying NOT NULL,
    file_asal_path character varying(255),
    diinput_oleh bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    harga_awal numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    CONSTRAINT stok_awals_sumber_check CHECK (((sumber)::text = ANY ((ARRAY['manual'::character varying, 'import'::character varying])::text[])))
);


--
-- Name: stok_awals_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.stok_awals_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: stok_awals_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.stok_awals_id_seq OWNED BY public.stok_awals.id;


--
-- Name: stok_opname_details; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.stok_opname_details (
    id bigint NOT NULL,
    stok_opname_id bigint NOT NULL,
    barang_id bigint NOT NULL,
    kode_barang character varying(50) NOT NULL,
    nama_barang character varying(255) NOT NULL,
    satuan character varying(100),
    qty_sistem numeric(14,3) NOT NULL,
    qty_fisik numeric(14,3),
    harga_satuan numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    catatan text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: stok_opname_details_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.stok_opname_details_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: stok_opname_details_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.stok_opname_details_id_seq OWNED BY public.stok_opname_details.id;


--
-- Name: stok_opnames; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.stok_opnames (
    id bigint NOT NULL,
    gudang_id bigint NOT NULL,
    periode date NOT NULL,
    tanggal_efektif date NOT NULL,
    status character varying(20) DEFAULT 'draft'::character varying NOT NULL,
    dibuat_oleh bigint,
    diterapkan_oleh bigint,
    diterapkan_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: stok_opnames_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.stok_opnames_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: stok_opnames_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.stok_opnames_id_seq OWNED BY public.stok_opnames.id;


--
-- Name: transfer_barangs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.transfer_barangs (
    id bigint NOT NULL,
    nomor_sj character varying(255) NOT NULL,
    permintaan_barang_id bigint,
    gudang_asal_id bigint NOT NULL,
    gudang_tujuan_id bigint,
    barang_id bigint NOT NULL,
    qty_diminta integer DEFAULT 0 NOT NULL,
    qty_dikirim integer DEFAULT 0 NOT NULL,
    qty_diterima integer DEFAULT 0 NOT NULL,
    status character varying(255) DEFAULT 'diminta'::character varying NOT NULL,
    foto_bukti_path character varying(255),
    supir_pengantar character varying(255),
    dikirim_oleh bigint,
    diterima_oleh bigint,
    dikirim_at timestamp(0) without time zone,
    diterima_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT transfer_barangs_status_check CHECK (((status)::text = ANY ((ARRAY['diminta'::character varying, 'dikirim'::character varying, 'diterima'::character varying])::text[])))
);


--
-- Name: transfer_barangs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.transfer_barangs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: transfer_barangs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.transfer_barangs_id_seq OWNED BY public.transfer_barangs.id;


--
-- Name: user_gudang; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.user_gudang (
    user_id bigint NOT NULL,
    gudang_id bigint NOT NULL
);


--
-- Name: users; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.users (
    id bigint NOT NULL,
    username character varying(50) NOT NULL,
    name character varying(255) NOT NULL,
    password character varying(255) NOT NULL,
    is_aktif boolean DEFAULT true NOT NULL,
    remember_token character varying(100),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
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
-- Name: audit_logs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.audit_logs ALTER COLUMN id SET DEFAULT nextval('public.audit_logs_id_seq'::regclass);


--
-- Name: barangs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.barangs ALTER COLUMN id SET DEFAULT nextval('public.barangs_id_seq'::regclass);


--
-- Name: divisi_pemintas id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.divisi_pemintas ALTER COLUMN id SET DEFAULT nextval('public.divisi_pemintas_id_seq'::regclass);


--
-- Name: failed_jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs ALTER COLUMN id SET DEFAULT nextval('public.failed_jobs_id_seq'::regclass);


--
-- Name: gudangs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.gudangs ALTER COLUMN id SET DEFAULT nextval('public.gudangs_id_seq'::regclass);


--
-- Name: jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jobs ALTER COLUMN id SET DEFAULT nextval('public.jobs_id_seq'::regclass);


--
-- Name: kategori_barangs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.kategori_barangs ALTER COLUMN id SET DEFAULT nextval('public.kategori_barangs_id_seq'::regclass);


--
-- Name: kwh_catatans id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.kwh_catatans ALTER COLUMN id SET DEFAULT nextval('public.kwh_catatans_id_seq'::regclass);


--
-- Name: master_barangs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.master_barangs ALTER COLUMN id SET DEFAULT nextval('public.master_barangs_id_seq'::regclass);


--
-- Name: migrations id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations ALTER COLUMN id SET DEFAULT nextval('public.migrations_id_seq'::regclass);


--
-- Name: penerimaan_barang_store_details id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.penerimaan_barang_store_details ALTER COLUMN id SET DEFAULT nextval('public.penerimaan_barang_store_details_id_seq'::regclass);


--
-- Name: penerimaan_barang_stores id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.penerimaan_barang_stores ALTER COLUMN id SET DEFAULT nextval('public.penerimaan_barang_stores_id_seq'::regclass);


--
-- Name: penerimaan_pemindahan_units id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.penerimaan_pemindahan_units ALTER COLUMN id SET DEFAULT nextval('public.penerimaan_pemindahan_units_id_seq'::regclass);


--
-- Name: penerimaan_unit_details id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.penerimaan_unit_details ALTER COLUMN id SET DEFAULT nextval('public.penerimaan_unit_details_id_seq'::regclass);


--
-- Name: penerimaan_units id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.penerimaan_units ALTER COLUMN id SET DEFAULT nextval('public.penerimaan_units_id_seq'::regclass);


--
-- Name: pengeluaran_unit_details id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pengeluaran_unit_details ALTER COLUMN id SET DEFAULT nextval('public.pengeluaran_unit_details_id_seq'::regclass);


--
-- Name: pengeluaran_units id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pengeluaran_units ALTER COLUMN id SET DEFAULT nextval('public.pengeluaran_units_id_seq'::regclass);


--
-- Name: pengembalian_units id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pengembalian_units ALTER COLUMN id SET DEFAULT nextval('public.pengembalian_units_id_seq'::regclass);


--
-- Name: permintaan_barang_details id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.permintaan_barang_details ALTER COLUMN id SET DEFAULT nextval('public.permintaan_barang_details_id_seq'::regclass);


--
-- Name: permintaan_barangs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.permintaan_barangs ALTER COLUMN id SET DEFAULT nextval('public.permintaan_barangs_id_seq'::regclass);


--
-- Name: permissions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.permissions ALTER COLUMN id SET DEFAULT nextval('public.permissions_id_seq'::regclass);


--
-- Name: roles id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.roles ALTER COLUMN id SET DEFAULT nextval('public.roles_id_seq'::regclass);


--
-- Name: satuan_konversis id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.satuan_konversis ALTER COLUMN id SET DEFAULT nextval('public.satuan_konversis_id_seq'::regclass);


--
-- Name: satuans id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.satuans ALTER COLUMN id SET DEFAULT nextval('public.satuans_id_seq'::regclass);


--
-- Name: stok_awals id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stok_awals ALTER COLUMN id SET DEFAULT nextval('public.stok_awals_id_seq'::regclass);


--
-- Name: stok_opname_details id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stok_opname_details ALTER COLUMN id SET DEFAULT nextval('public.stok_opname_details_id_seq'::regclass);


--
-- Name: stok_opnames id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stok_opnames ALTER COLUMN id SET DEFAULT nextval('public.stok_opnames_id_seq'::regclass);


--
-- Name: transfer_barangs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.transfer_barangs ALTER COLUMN id SET DEFAULT nextval('public.transfer_barangs_id_seq'::regclass);


--
-- Name: users id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- Name: audit_logs audit_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.audit_logs
    ADD CONSTRAINT audit_logs_pkey PRIMARY KEY (id);


--
-- Name: barangs barangs_gudang_id_kode_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.barangs
    ADD CONSTRAINT barangs_gudang_id_kode_unique UNIQUE (gudang_id, kode);


--
-- Name: barangs barangs_master_barang_gudang_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.barangs
    ADD CONSTRAINT barangs_master_barang_gudang_unique UNIQUE (master_barang_id, gudang_id);


--
-- Name: barangs barangs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.barangs
    ADD CONSTRAINT barangs_pkey PRIMARY KEY (id);


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
-- Name: divisi_pemintas divisi_pemintas_gudang_id_nama_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.divisi_pemintas
    ADD CONSTRAINT divisi_pemintas_gudang_id_nama_unique UNIQUE (gudang_id, nama);


--
-- Name: divisi_pemintas divisi_pemintas_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.divisi_pemintas
    ADD CONSTRAINT divisi_pemintas_pkey PRIMARY KEY (id);


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
-- Name: gudangs gudangs_kode_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.gudangs
    ADD CONSTRAINT gudangs_kode_unique UNIQUE (kode);


--
-- Name: gudangs gudangs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.gudangs
    ADD CONSTRAINT gudangs_pkey PRIMARY KEY (id);


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
-- Name: kategori_barangs kategori_barangs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.kategori_barangs
    ADD CONSTRAINT kategori_barangs_pkey PRIMARY KEY (id);


--
-- Name: kwh_catatans kwh_catatans_gudang_id_tanggal_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.kwh_catatans
    ADD CONSTRAINT kwh_catatans_gudang_id_tanggal_unique UNIQUE (gudang_id, tanggal);


--
-- Name: kwh_catatans kwh_catatans_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.kwh_catatans
    ADD CONSTRAINT kwh_catatans_pkey PRIMARY KEY (id);


--
-- Name: master_barangs master_barangs_kode_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.master_barangs
    ADD CONSTRAINT master_barangs_kode_unique UNIQUE (kode);


--
-- Name: master_barangs master_barangs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.master_barangs
    ADD CONSTRAINT master_barangs_pkey PRIMARY KEY (id);


--
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- Name: model_has_permissions model_has_permissions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.model_has_permissions
    ADD CONSTRAINT model_has_permissions_pkey PRIMARY KEY (permission_id, model_id, model_type);


--
-- Name: model_has_roles model_has_roles_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.model_has_roles
    ADD CONSTRAINT model_has_roles_pkey PRIMARY KEY (role_id, model_id, model_type);


--
-- Name: password_reset_tokens password_reset_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.password_reset_tokens
    ADD CONSTRAINT password_reset_tokens_pkey PRIMARY KEY (username);


--
-- Name: penerimaan_barang_store_details penerimaan_barang_store_details_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.penerimaan_barang_store_details
    ADD CONSTRAINT penerimaan_barang_store_details_pkey PRIMARY KEY (id);


--
-- Name: penerimaan_barang_stores penerimaan_barang_stores_nomor_penerimaan_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.penerimaan_barang_stores
    ADD CONSTRAINT penerimaan_barang_stores_nomor_penerimaan_unique UNIQUE (nomor_penerimaan);


--
-- Name: penerimaan_barang_stores penerimaan_barang_stores_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.penerimaan_barang_stores
    ADD CONSTRAINT penerimaan_barang_stores_pkey PRIMARY KEY (id);


--
-- Name: penerimaan_pemindahan_units penerimaan_pemindahan_units_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.penerimaan_pemindahan_units
    ADD CONSTRAINT penerimaan_pemindahan_units_pkey PRIMARY KEY (id);


--
-- Name: penerimaan_pemindahan_units penerimaan_pemindahan_units_transfer_barang_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.penerimaan_pemindahan_units
    ADD CONSTRAINT penerimaan_pemindahan_units_transfer_barang_id_unique UNIQUE (transfer_barang_id);


--
-- Name: penerimaan_unit_details penerimaan_unit_details_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.penerimaan_unit_details
    ADD CONSTRAINT penerimaan_unit_details_pkey PRIMARY KEY (id);


--
-- Name: penerimaan_units penerimaan_units_nomor_rcv_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.penerimaan_units
    ADD CONSTRAINT penerimaan_units_nomor_rcv_unique UNIQUE (nomor_rcv);


--
-- Name: penerimaan_units penerimaan_units_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.penerimaan_units
    ADD CONSTRAINT penerimaan_units_pkey PRIMARY KEY (id);


--
-- Name: pengeluaran_unit_details pengeluaran_unit_details_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pengeluaran_unit_details
    ADD CONSTRAINT pengeluaran_unit_details_pkey PRIMARY KEY (id);


--
-- Name: pengeluaran_unit_number_migration_backups pengeluaran_unit_number_migration_backups_nomor_bpb_lama_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pengeluaran_unit_number_migration_backups
    ADD CONSTRAINT pengeluaran_unit_number_migration_backups_nomor_bpb_lama_unique UNIQUE (nomor_bpb_lama);


--
-- Name: pengeluaran_unit_number_migration_backups pengeluaran_unit_number_migration_backups_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pengeluaran_unit_number_migration_backups
    ADD CONSTRAINT pengeluaran_unit_number_migration_backups_pkey PRIMARY KEY (pengeluaran_unit_id);


--
-- Name: pengeluaran_units pengeluaran_units_nomor_bpb_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pengeluaran_units
    ADD CONSTRAINT pengeluaran_units_nomor_bpb_unique UNIQUE (nomor_bpb);


--
-- Name: pengeluaran_units pengeluaran_units_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pengeluaran_units
    ADD CONSTRAINT pengeluaran_units_pkey PRIMARY KEY (id);


--
-- Name: pengembalian_units pengembalian_units_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pengembalian_units
    ADD CONSTRAINT pengembalian_units_pkey PRIMARY KEY (id);


--
-- Name: permintaan_barang_details permintaan_barang_details_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.permintaan_barang_details
    ADD CONSTRAINT permintaan_barang_details_pkey PRIMARY KEY (id);


--
-- Name: permintaan_barangs permintaan_barangs_nomor_pr_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.permintaan_barangs
    ADD CONSTRAINT permintaan_barangs_nomor_pr_unique UNIQUE (nomor_pr);


--
-- Name: permintaan_barangs permintaan_barangs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.permintaan_barangs
    ADD CONSTRAINT permintaan_barangs_pkey PRIMARY KEY (id);


--
-- Name: permissions permissions_name_guard_name_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.permissions
    ADD CONSTRAINT permissions_name_guard_name_unique UNIQUE (name, guard_name);


--
-- Name: permissions permissions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.permissions
    ADD CONSTRAINT permissions_pkey PRIMARY KEY (id);


--
-- Name: role_has_permissions role_has_permissions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.role_has_permissions
    ADD CONSTRAINT role_has_permissions_pkey PRIMARY KEY (permission_id, role_id);


--
-- Name: roles roles_name_guard_name_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_name_guard_name_unique UNIQUE (name, guard_name);


--
-- Name: roles roles_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_pkey PRIMARY KEY (id);


--
-- Name: satuan_konversis satuan_konversis_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.satuan_konversis
    ADD CONSTRAINT satuan_konversis_pkey PRIMARY KEY (id);


--
-- Name: satuans satuans_kode_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.satuans
    ADD CONSTRAINT satuans_kode_unique UNIQUE (kode);


--
-- Name: satuans satuans_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.satuans
    ADD CONSTRAINT satuans_pkey PRIMARY KEY (id);


--
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);


--
-- Name: stok_awals stok_awals_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stok_awals
    ADD CONSTRAINT stok_awals_pkey PRIMARY KEY (id);


--
-- Name: stok_opname_details stok_opname_details_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stok_opname_details
    ADD CONSTRAINT stok_opname_details_pkey PRIMARY KEY (id);


--
-- Name: stok_opname_details stok_opname_details_stok_opname_id_barang_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stok_opname_details
    ADD CONSTRAINT stok_opname_details_stok_opname_id_barang_id_unique UNIQUE (stok_opname_id, barang_id);


--
-- Name: stok_opnames stok_opnames_gudang_id_periode_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stok_opnames
    ADD CONSTRAINT stok_opnames_gudang_id_periode_unique UNIQUE (gudang_id, periode);


--
-- Name: stok_opnames stok_opnames_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stok_opnames
    ADD CONSTRAINT stok_opnames_pkey PRIMARY KEY (id);


--
-- Name: transfer_barangs transfer_barangs_nomor_sj_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.transfer_barangs
    ADD CONSTRAINT transfer_barangs_nomor_sj_unique UNIQUE (nomor_sj);


--
-- Name: transfer_barangs transfer_barangs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.transfer_barangs
    ADD CONSTRAINT transfer_barangs_pkey PRIMARY KEY (id);


--
-- Name: stok_awals unique_gudang_barang_stok_awal; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stok_awals
    ADD CONSTRAINT unique_gudang_barang_stok_awal UNIQUE (gudang_id, barang_id);


--
-- Name: user_gudang user_gudang_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_gudang
    ADD CONSTRAINT user_gudang_pkey PRIMARY KEY (user_id, gudang_id);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: users users_username_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_username_unique UNIQUE (username);


--
-- Name: audit_logs_action_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX audit_logs_action_index ON public.audit_logs USING btree (action);


--
-- Name: audit_logs_causer_type_causer_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX audit_logs_causer_type_causer_id_index ON public.audit_logs USING btree (causer_type, causer_id);


--
-- Name: audit_logs_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX audit_logs_created_at_index ON public.audit_logs USING btree (created_at);


--
-- Name: audit_logs_subject_type_subject_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX audit_logs_subject_type_subject_id_index ON public.audit_logs USING btree (subject_type, subject_id);


--
-- Name: cache_expiration_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cache_expiration_index ON public.cache USING btree (expiration);


--
-- Name: cache_locks_expiration_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cache_locks_expiration_index ON public.cache_locks USING btree (expiration);


--
-- Name: failed_jobs_connection_queue_failed_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX failed_jobs_connection_queue_failed_at_index ON public.failed_jobs USING btree (connection, queue, failed_at);


--
-- Name: idx_penerimaan_barang_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_penerimaan_barang_id ON public.penerimaan_barang_stores USING btree (barang_id);


--
-- Name: idx_penerimaan_gudang_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_penerimaan_gudang_id ON public.penerimaan_barang_stores USING btree (gudang_id);


--
-- Name: idx_penerimaan_tanggal; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_penerimaan_tanggal ON public.penerimaan_barang_stores USING btree (tanggal_diterima);


--
-- Name: idx_permintaan_detail_request_item; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_permintaan_detail_request_item ON public.permintaan_barang_details USING btree (permintaan_barang_id, barang_id);


--
-- Name: idx_permintaan_gudang_peminta; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_permintaan_gudang_peminta ON public.permintaan_barangs USING btree (gudang_peminta_id);


--
-- Name: idx_permintaan_status; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_permintaan_status ON public.permintaan_barangs USING btree (status);


--
-- Name: idx_permintaan_store_status_created; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_permintaan_store_status_created ON public.permintaan_barangs USING btree (gudang_tujuan_id, status, created_at);


--
-- Name: idx_transfer_barang_status; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_transfer_barang_status ON public.transfer_barangs USING btree (barang_id, status);


--
-- Name: idx_transfer_gudang_asal; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_transfer_gudang_asal ON public.transfer_barangs USING btree (gudang_asal_id);


--
-- Name: idx_transfer_gudang_tujuan; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_transfer_gudang_tujuan ON public.transfer_barangs USING btree (gudang_tujuan_id);


--
-- Name: idx_transfer_status; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_transfer_status ON public.transfer_barangs USING btree (status);


--
-- Name: idx_transfer_store_item_status; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_transfer_store_item_status ON public.transfer_barangs USING btree (gudang_asal_id, barang_id, status);


--
-- Name: idx_transfer_store_status_created; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_transfer_store_status_created ON public.transfer_barangs USING btree (gudang_asal_id, status, created_at);


--
-- Name: jobs_queue_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX jobs_queue_index ON public.jobs USING btree (queue);


--
-- Name: model_has_permissions_model_id_model_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX model_has_permissions_model_id_model_type_index ON public.model_has_permissions USING btree (model_id, model_type);


--
-- Name: model_has_roles_model_id_model_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX model_has_roles_model_id_model_type_index ON public.model_has_roles USING btree (model_id, model_type);


--
-- Name: penerimaan_pemindahan_units_barang_id_diterima_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX penerimaan_pemindahan_units_barang_id_diterima_at_index ON public.penerimaan_pemindahan_units USING btree (barang_id, diterima_at);


--
-- Name: penerimaan_pemindahan_units_gudang_id_diterima_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX penerimaan_pemindahan_units_gudang_id_diterima_at_index ON public.penerimaan_pemindahan_units USING btree (gudang_id, diterima_at);


--
-- Name: penerimaan_unit_details_penerimaan_unit_id_barang_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX penerimaan_unit_details_penerimaan_unit_id_barang_id_index ON public.penerimaan_unit_details USING btree (penerimaan_unit_id, barang_id);


--
-- Name: penerimaan_units_gudang_id_tanggal_terima_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX penerimaan_units_gudang_id_tanggal_terima_index ON public.penerimaan_units USING btree (gudang_id, tanggal_terima);


--
-- Name: pengeluaran_unit_details_pengeluaran_unit_id_barang_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX pengeluaran_unit_details_pengeluaran_unit_id_barang_id_index ON public.pengeluaran_unit_details USING btree (pengeluaran_unit_id, barang_id);


--
-- Name: pengeluaran_units_gudang_id_status_verifikasi_tanggal_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX pengeluaran_units_gudang_id_status_verifikasi_tanggal_index ON public.pengeluaran_units USING btree (gudang_id, status_verifikasi, tanggal);


--
-- Name: pengeluaran_units_gudang_id_tanggal_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX pengeluaran_units_gudang_id_tanggal_index ON public.pengeluaran_units USING btree (gudang_id, tanggal);


--
-- Name: pengembalian_units_gudang_id_bagian_peminta_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX pengembalian_units_gudang_id_bagian_peminta_index ON public.pengembalian_units USING btree (gudang_id, bagian_peminta);


--
-- Name: pengembalian_units_gudang_id_tanggal_kembali_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX pengembalian_units_gudang_id_tanggal_kembali_index ON public.pengembalian_units USING btree (gudang_id, tanggal_kembali);


--
-- Name: ppu_gudang_status_diterima_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ppu_gudang_status_diterima_idx ON public.penerimaan_pemindahan_units USING btree (gudang_id, status_verifikasi, diterima_at);


--
-- Name: sessions_last_activity_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_last_activity_index ON public.sessions USING btree (last_activity);


--
-- Name: sessions_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_user_id_index ON public.sessions USING btree (user_id);


--
-- Name: stok_opname_details_barang_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX stok_opname_details_barang_id_index ON public.stok_opname_details USING btree (barang_id);


--
-- Name: stok_opnames_gudang_id_status_tanggal_efektif_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX stok_opnames_gudang_id_status_tanggal_efektif_index ON public.stok_opnames USING btree (gudang_id, status, tanggal_efektif);


--
-- Name: barangs barangs_gudang_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.barangs
    ADD CONSTRAINT barangs_gudang_id_foreign FOREIGN KEY (gudang_id) REFERENCES public.gudangs(id) ON DELETE SET NULL;


--
-- Name: barangs barangs_kategori_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.barangs
    ADD CONSTRAINT barangs_kategori_id_foreign FOREIGN KEY (kategori_id) REFERENCES public.kategori_barangs(id) ON DELETE SET NULL;


--
-- Name: barangs barangs_master_barang_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.barangs
    ADD CONSTRAINT barangs_master_barang_id_foreign FOREIGN KEY (master_barang_id) REFERENCES public.master_barangs(id) ON DELETE SET NULL;


--
-- Name: barangs barangs_satuan_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.barangs
    ADD CONSTRAINT barangs_satuan_id_foreign FOREIGN KEY (satuan_id) REFERENCES public.satuans(id) ON DELETE SET NULL;


--
-- Name: divisi_pemintas divisi_pemintas_gudang_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.divisi_pemintas
    ADD CONSTRAINT divisi_pemintas_gudang_id_foreign FOREIGN KEY (gudang_id) REFERENCES public.gudangs(id) ON DELETE CASCADE;


--
-- Name: kategori_barangs kategori_barangs_gudang_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.kategori_barangs
    ADD CONSTRAINT kategori_barangs_gudang_id_foreign FOREIGN KEY (gudang_id) REFERENCES public.gudangs(id) ON DELETE CASCADE;


--
-- Name: kwh_catatans kwh_catatans_gudang_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.kwh_catatans
    ADD CONSTRAINT kwh_catatans_gudang_id_foreign FOREIGN KEY (gudang_id) REFERENCES public.gudangs(id) ON DELETE CASCADE;


--
-- Name: kwh_catatans kwh_catatans_pencatat_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.kwh_catatans
    ADD CONSTRAINT kwh_catatans_pencatat_id_foreign FOREIGN KEY (pencatat_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: master_barangs master_barangs_kategori_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.master_barangs
    ADD CONSTRAINT master_barangs_kategori_id_foreign FOREIGN KEY (kategori_id) REFERENCES public.kategori_barangs(id) ON DELETE SET NULL;


--
-- Name: master_barangs master_barangs_satuan_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.master_barangs
    ADD CONSTRAINT master_barangs_satuan_id_foreign FOREIGN KEY (satuan_id) REFERENCES public.satuans(id) ON DELETE SET NULL;


--
-- Name: model_has_permissions model_has_permissions_permission_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.model_has_permissions
    ADD CONSTRAINT model_has_permissions_permission_id_foreign FOREIGN KEY (permission_id) REFERENCES public.permissions(id) ON DELETE CASCADE;


--
-- Name: model_has_roles model_has_roles_role_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.model_has_roles
    ADD CONSTRAINT model_has_roles_role_id_foreign FOREIGN KEY (role_id) REFERENCES public.roles(id) ON DELETE CASCADE;


--
-- Name: penerimaan_barang_store_details penerimaan_barang_store_details_barang_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.penerimaan_barang_store_details
    ADD CONSTRAINT penerimaan_barang_store_details_barang_id_foreign FOREIGN KEY (barang_id) REFERENCES public.barangs(id) ON DELETE CASCADE;


--
-- Name: penerimaan_barang_store_details penerimaan_barang_store_details_penerimaan_barang_store_id_fore; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.penerimaan_barang_store_details
    ADD CONSTRAINT penerimaan_barang_store_details_penerimaan_barang_store_id_fore FOREIGN KEY (penerimaan_barang_store_id) REFERENCES public.penerimaan_barang_stores(id) ON DELETE CASCADE;


--
-- Name: penerimaan_barang_store_details penerimaan_barang_store_details_satuan_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.penerimaan_barang_store_details
    ADD CONSTRAINT penerimaan_barang_store_details_satuan_id_foreign FOREIGN KEY (satuan_id) REFERENCES public.satuans(id) ON DELETE SET NULL;


--
-- Name: penerimaan_barang_store_details penerimaan_barang_store_details_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.penerimaan_barang_store_details
    ADD CONSTRAINT penerimaan_barang_store_details_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: penerimaan_barang_stores penerimaan_barang_stores_barang_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.penerimaan_barang_stores
    ADD CONSTRAINT penerimaan_barang_stores_barang_id_foreign FOREIGN KEY (barang_id) REFERENCES public.barangs(id) ON DELETE CASCADE;


--
-- Name: penerimaan_barang_stores penerimaan_barang_stores_diterima_oleh_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.penerimaan_barang_stores
    ADD CONSTRAINT penerimaan_barang_stores_diterima_oleh_foreign FOREIGN KEY (diterima_oleh) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: penerimaan_barang_stores penerimaan_barang_stores_follow_up_completed_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.penerimaan_barang_stores
    ADD CONSTRAINT penerimaan_barang_stores_follow_up_completed_by_foreign FOREIGN KEY (follow_up_completed_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: penerimaan_barang_stores penerimaan_barang_stores_gudang_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.penerimaan_barang_stores
    ADD CONSTRAINT penerimaan_barang_stores_gudang_id_foreign FOREIGN KEY (gudang_id) REFERENCES public.gudangs(id) ON DELETE CASCADE;


--
-- Name: penerimaan_barang_stores penerimaan_barang_stores_satuan_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.penerimaan_barang_stores
    ADD CONSTRAINT penerimaan_barang_stores_satuan_id_foreign FOREIGN KEY (satuan_id) REFERENCES public.satuans(id) ON DELETE SET NULL;


--
-- Name: penerimaan_barang_stores penerimaan_barang_stores_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.penerimaan_barang_stores
    ADD CONSTRAINT penerimaan_barang_stores_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: penerimaan_pemindahan_units penerimaan_pemindahan_units_barang_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.penerimaan_pemindahan_units
    ADD CONSTRAINT penerimaan_pemindahan_units_barang_id_foreign FOREIGN KEY (barang_id) REFERENCES public.barangs(id) ON DELETE RESTRICT;


--
-- Name: penerimaan_pemindahan_units penerimaan_pemindahan_units_diterima_oleh_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.penerimaan_pemindahan_units
    ADD CONSTRAINT penerimaan_pemindahan_units_diterima_oleh_foreign FOREIGN KEY (diterima_oleh) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: penerimaan_pemindahan_units penerimaan_pemindahan_units_diverifikasi_oleh_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.penerimaan_pemindahan_units
    ADD CONSTRAINT penerimaan_pemindahan_units_diverifikasi_oleh_foreign FOREIGN KEY (diverifikasi_oleh) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: penerimaan_pemindahan_units penerimaan_pemindahan_units_gudang_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.penerimaan_pemindahan_units
    ADD CONSTRAINT penerimaan_pemindahan_units_gudang_id_foreign FOREIGN KEY (gudang_id) REFERENCES public.gudangs(id) ON DELETE RESTRICT;


--
-- Name: penerimaan_pemindahan_units penerimaan_pemindahan_units_transfer_barang_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.penerimaan_pemindahan_units
    ADD CONSTRAINT penerimaan_pemindahan_units_transfer_barang_id_foreign FOREIGN KEY (transfer_barang_id) REFERENCES public.transfer_barangs(id) ON DELETE RESTRICT;


--
-- Name: penerimaan_unit_details penerimaan_unit_details_barang_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.penerimaan_unit_details
    ADD CONSTRAINT penerimaan_unit_details_barang_id_foreign FOREIGN KEY (barang_id) REFERENCES public.barangs(id) ON DELETE SET NULL;


--
-- Name: penerimaan_unit_details penerimaan_unit_details_penerimaan_unit_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.penerimaan_unit_details
    ADD CONSTRAINT penerimaan_unit_details_penerimaan_unit_id_foreign FOREIGN KEY (penerimaan_unit_id) REFERENCES public.penerimaan_units(id) ON DELETE CASCADE;


--
-- Name: penerimaan_units penerimaan_units_diterima_oleh_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.penerimaan_units
    ADD CONSTRAINT penerimaan_units_diterima_oleh_foreign FOREIGN KEY (diterima_oleh) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: penerimaan_units penerimaan_units_diverifikasi_oleh_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.penerimaan_units
    ADD CONSTRAINT penerimaan_units_diverifikasi_oleh_foreign FOREIGN KEY (diverifikasi_oleh) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: penerimaan_units penerimaan_units_gudang_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.penerimaan_units
    ADD CONSTRAINT penerimaan_units_gudang_id_foreign FOREIGN KEY (gudang_id) REFERENCES public.gudangs(id) ON DELETE RESTRICT;


--
-- Name: pengeluaran_unit_details pengeluaran_unit_details_barang_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pengeluaran_unit_details
    ADD CONSTRAINT pengeluaran_unit_details_barang_id_foreign FOREIGN KEY (barang_id) REFERENCES public.barangs(id) ON DELETE SET NULL;


--
-- Name: pengeluaran_unit_details pengeluaran_unit_details_pengeluaran_unit_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pengeluaran_unit_details
    ADD CONSTRAINT pengeluaran_unit_details_pengeluaran_unit_id_foreign FOREIGN KEY (pengeluaran_unit_id) REFERENCES public.pengeluaran_units(id) ON DELETE CASCADE;


--
-- Name: pengeluaran_units pengeluaran_units_dicatat_oleh_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pengeluaran_units
    ADD CONSTRAINT pengeluaran_units_dicatat_oleh_foreign FOREIGN KEY (dicatat_oleh) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: pengeluaran_units pengeluaran_units_diverifikasi_oleh_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pengeluaran_units
    ADD CONSTRAINT pengeluaran_units_diverifikasi_oleh_foreign FOREIGN KEY (diverifikasi_oleh) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: pengeluaran_units pengeluaran_units_divisi_peminta_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pengeluaran_units
    ADD CONSTRAINT pengeluaran_units_divisi_peminta_id_foreign FOREIGN KEY (divisi_peminta_id) REFERENCES public.divisi_pemintas(id) ON DELETE SET NULL;


--
-- Name: pengeluaran_units pengeluaran_units_gudang_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pengeluaran_units
    ADD CONSTRAINT pengeluaran_units_gudang_id_foreign FOREIGN KEY (gudang_id) REFERENCES public.gudangs(id) ON DELETE RESTRICT;


--
-- Name: pengembalian_units pengembalian_units_barang_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pengembalian_units
    ADD CONSTRAINT pengembalian_units_barang_id_foreign FOREIGN KEY (barang_id) REFERENCES public.barangs(id) ON DELETE SET NULL;


--
-- Name: pengembalian_units pengembalian_units_dicatat_oleh_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pengembalian_units
    ADD CONSTRAINT pengembalian_units_dicatat_oleh_foreign FOREIGN KEY (dicatat_oleh) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: pengembalian_units pengembalian_units_gudang_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pengembalian_units
    ADD CONSTRAINT pengembalian_units_gudang_id_foreign FOREIGN KEY (gudang_id) REFERENCES public.gudangs(id) ON DELETE CASCADE;


--
-- Name: pengembalian_units pengembalian_units_pengeluaran_unit_detail_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pengembalian_units
    ADD CONSTRAINT pengembalian_units_pengeluaran_unit_detail_id_foreign FOREIGN KEY (pengeluaran_unit_detail_id) REFERENCES public.pengeluaran_unit_details(id) ON DELETE SET NULL;


--
-- Name: pengembalian_units pengembalian_units_pengeluaran_unit_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pengembalian_units
    ADD CONSTRAINT pengembalian_units_pengeluaran_unit_id_foreign FOREIGN KEY (pengeluaran_unit_id) REFERENCES public.pengeluaran_units(id) ON DELETE CASCADE;


--
-- Name: permintaan_barang_details permintaan_barang_details_barang_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.permintaan_barang_details
    ADD CONSTRAINT permintaan_barang_details_barang_id_foreign FOREIGN KEY (barang_id) REFERENCES public.barangs(id) ON DELETE CASCADE;


--
-- Name: permintaan_barang_details permintaan_barang_details_permintaan_barang_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.permintaan_barang_details
    ADD CONSTRAINT permintaan_barang_details_permintaan_barang_id_foreign FOREIGN KEY (permintaan_barang_id) REFERENCES public.permintaan_barangs(id) ON DELETE CASCADE;


--
-- Name: permintaan_barang_details permintaan_barang_details_satuan_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.permintaan_barang_details
    ADD CONSTRAINT permintaan_barang_details_satuan_id_foreign FOREIGN KEY (satuan_id) REFERENCES public.satuans(id) ON DELETE SET NULL;


--
-- Name: permintaan_barangs permintaan_barangs_dibuat_oleh_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.permintaan_barangs
    ADD CONSTRAINT permintaan_barangs_dibuat_oleh_foreign FOREIGN KEY (dibuat_oleh) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: permintaan_barangs permintaan_barangs_gudang_peminta_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.permintaan_barangs
    ADD CONSTRAINT permintaan_barangs_gudang_peminta_id_foreign FOREIGN KEY (gudang_peminta_id) REFERENCES public.gudangs(id) ON DELETE CASCADE;


--
-- Name: permintaan_barangs permintaan_barangs_gudang_tujuan_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.permintaan_barangs
    ADD CONSTRAINT permintaan_barangs_gudang_tujuan_id_foreign FOREIGN KEY (gudang_tujuan_id) REFERENCES public.gudangs(id) ON DELETE CASCADE;


--
-- Name: role_has_permissions role_has_permissions_permission_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.role_has_permissions
    ADD CONSTRAINT role_has_permissions_permission_id_foreign FOREIGN KEY (permission_id) REFERENCES public.permissions(id) ON DELETE CASCADE;


--
-- Name: role_has_permissions role_has_permissions_role_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.role_has_permissions
    ADD CONSTRAINT role_has_permissions_role_id_foreign FOREIGN KEY (role_id) REFERENCES public.roles(id) ON DELETE CASCADE;


--
-- Name: satuan_konversis satuan_konversis_barang_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.satuan_konversis
    ADD CONSTRAINT satuan_konversis_barang_id_foreign FOREIGN KEY (barang_id) REFERENCES public.barangs(id) ON DELETE CASCADE;


--
-- Name: satuan_konversis satuan_konversis_satuan_turunan_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.satuan_konversis
    ADD CONSTRAINT satuan_konversis_satuan_turunan_id_foreign FOREIGN KEY (satuan_turunan_id) REFERENCES public.satuans(id) ON DELETE CASCADE;


--
-- Name: satuans satuans_gudang_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.satuans
    ADD CONSTRAINT satuans_gudang_id_foreign FOREIGN KEY (gudang_id) REFERENCES public.gudangs(id) ON DELETE SET NULL;


--
-- Name: stok_awals stok_awals_barang_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stok_awals
    ADD CONSTRAINT stok_awals_barang_id_foreign FOREIGN KEY (barang_id) REFERENCES public.barangs(id) ON DELETE CASCADE;


--
-- Name: stok_awals stok_awals_diinput_oleh_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stok_awals
    ADD CONSTRAINT stok_awals_diinput_oleh_foreign FOREIGN KEY (diinput_oleh) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: stok_awals stok_awals_gudang_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stok_awals
    ADD CONSTRAINT stok_awals_gudang_id_foreign FOREIGN KEY (gudang_id) REFERENCES public.gudangs(id) ON DELETE SET NULL;


--
-- Name: stok_opname_details stok_opname_details_barang_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stok_opname_details
    ADD CONSTRAINT stok_opname_details_barang_id_foreign FOREIGN KEY (barang_id) REFERENCES public.barangs(id) ON DELETE RESTRICT;


--
-- Name: stok_opname_details stok_opname_details_stok_opname_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stok_opname_details
    ADD CONSTRAINT stok_opname_details_stok_opname_id_foreign FOREIGN KEY (stok_opname_id) REFERENCES public.stok_opnames(id) ON DELETE CASCADE;


--
-- Name: stok_opnames stok_opnames_dibuat_oleh_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stok_opnames
    ADD CONSTRAINT stok_opnames_dibuat_oleh_foreign FOREIGN KEY (dibuat_oleh) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: stok_opnames stok_opnames_diterapkan_oleh_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stok_opnames
    ADD CONSTRAINT stok_opnames_diterapkan_oleh_foreign FOREIGN KEY (diterapkan_oleh) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: stok_opnames stok_opnames_gudang_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stok_opnames
    ADD CONSTRAINT stok_opnames_gudang_id_foreign FOREIGN KEY (gudang_id) REFERENCES public.gudangs(id) ON DELETE RESTRICT;


--
-- Name: transfer_barangs transfer_barangs_barang_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.transfer_barangs
    ADD CONSTRAINT transfer_barangs_barang_id_foreign FOREIGN KEY (barang_id) REFERENCES public.barangs(id) ON DELETE CASCADE;


--
-- Name: transfer_barangs transfer_barangs_dikirim_oleh_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.transfer_barangs
    ADD CONSTRAINT transfer_barangs_dikirim_oleh_foreign FOREIGN KEY (dikirim_oleh) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: transfer_barangs transfer_barangs_diterima_oleh_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.transfer_barangs
    ADD CONSTRAINT transfer_barangs_diterima_oleh_foreign FOREIGN KEY (diterima_oleh) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: transfer_barangs transfer_barangs_gudang_asal_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.transfer_barangs
    ADD CONSTRAINT transfer_barangs_gudang_asal_id_foreign FOREIGN KEY (gudang_asal_id) REFERENCES public.gudangs(id) ON DELETE CASCADE;


--
-- Name: transfer_barangs transfer_barangs_gudang_tujuan_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.transfer_barangs
    ADD CONSTRAINT transfer_barangs_gudang_tujuan_id_foreign FOREIGN KEY (gudang_tujuan_id) REFERENCES public.gudangs(id) ON DELETE CASCADE;


--
-- Name: transfer_barangs transfer_barangs_permintaan_barang_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.transfer_barangs
    ADD CONSTRAINT transfer_barangs_permintaan_barang_id_foreign FOREIGN KEY (permintaan_barang_id) REFERENCES public.permintaan_barangs(id) ON DELETE SET NULL;


--
-- Name: user_gudang user_gudang_gudang_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_gudang
    ADD CONSTRAINT user_gudang_gudang_id_foreign FOREIGN KEY (gudang_id) REFERENCES public.gudangs(id) ON DELETE CASCADE;


--
-- Name: user_gudang user_gudang_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_gudang
    ADD CONSTRAINT user_gudang_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- PostgreSQL database dump complete
--

\unrestrict YcIdgE4b0vwdBVYi8ZAOcpZsKQsavwZFgmC21wAWhneVghzc3MfSMccfBnpUgF9

--
-- PostgreSQL database dump
--

\restrict bNLo4Ngupdeps3IaP9eEEx4hetvkQDd40wAeFJlTAAqnLALY4EoHob24lQ6clzg

-- Dumped from database version 17.11
-- Dumped by pg_dump version 17.11

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Data for Name: migrations; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.migrations (id, migration, batch) FROM stdin;
1	0001_01_01_000000_create_users_table	1
2	0001_01_01_000001_create_cache_table	1
3	0001_01_01_000002_create_jobs_table	1
4	2026_09_17_021011_create_permission_tables	1
5	2026_09_17_022000_create_gudangs_table	1
6	2026_09_17_022140_create_satuans_table	1
7	2026_09_17_022150_create_kategori_barangs_table	1
8	2026_09_17_072541_add_vector_columns_to_master_tables	1
9	2026_09_20_001543_add_deleted_at_to_users_table	1
10	2026_09_20_014049_add_details_to_roles_table	1
11	2026_09_20_040000_add_gudang_id_to_master_tables	1
12	2026_09_21_000001_create_audit_logs_table	1
13	2026_09_22_000001_scope_barang_code_per_gudang	1
14	2026_09_22_100001_create_store_transaction_tables	1
15	2026_09_22_200000_add_performance_indexes_to_store_tables	1
16	2026_09_23_050000_add_foto_path_to_barangs_table	1
17	2026_09_23_060000_create_stok_awals_table	1
18	2026_09_23_080000_add_lokasi_rak_to_barangs_table	1
19	2026_09_24_000001_drop_usulan_barangs_table	1
20	2026_09_24_100000_create_penerimaan_barang_store_details_table	1
21	2026_09_26_000001_add_rekap_fields_to_store_receipts	1
22	2026_09_26_000002_add_pemakai_to_store_receipts	1
23	2026_09_27_000001_add_source_groups_to_store_receipts	1
24	2026_09_28_000001_add_pemakai_to_penerimaan_barang_store_details_table	1
25	2026_09_28_143848_add_follow_up_tracking_to_penerimaan_barang_stores_table	1
26	2026_09_29_070000_add_semantic_vectors_to_store_search_tables	1
27	2026_09_29_080000_fix_user_semantic_vector_column	1
28	2026_09_29_090000_add_audit_log_semantic_vectors	1
29	2026_09_29_100000_add_store_expenditure_indexes	1
30	2026_09_29_110000_create_penerimaan_pemindahan_units_table	1
31	2026_10_10_103024_add_qty_lebih_to_penerimaan_barang_store_details_table	1
32	2026_10_12_184633_make_gudang_tujuan_id_nullable_in_transfer_barangs_table	1
33	2026_10_13_200559_create_master_barangs_table	1
34	2026_10_13_200601_add_master_barang_id_to_barangs_table	1
35	2026_10_14_094906_add_semantic_vector_to_master_barangs_table	1
36	2026_10_14_100015_add_description_to_kategori_barangs_table	1
37	2026_10_14_160340_create_master_barang_imports_table	1
38	2026_10_16_111505_create_master_barang_import_rows_table	2
39	2026_10_16_175120_remove_master_barang_import_tables_and_permission	2
41	2026_10_21_170000_add_menu_specific_crud_permissions	3
42	2026_10_26_142238_add_admin_gu_request_permissions	4
43	2026_10_29_111455_create_pengeluaran_units_table	5
44	2026_10_29_144500_create_penerimaan_units_table	6
46	2026_10_31_141500_create_divisi_pemintas_and_enhance_pengeluaran_units	7
47	2026_11_01_135652_widen_divisi_peminta_name	8
48	2026_11_01_181427_add_warehouse_date_index_to_pengeluaran_units_table	8
49	2026_11_02_120301_create_kwh_catatan_table	9
50	2026_11_02_120320_add_harga_beli_to_penerimaan_unit_details_table	9
51	2026_11_03_071145_add_qty_kembali_to_pengeluaran_unit_details_table	10
52	2026_11_03_225031_extend_stok_awals_for_unit_balance_import	11
53	2026_11_04_053603_add_verified_quantities_to_penerimaan_unit_details_table	12
54	2026_11_15_230822_add_qty_kembali_rekonsiliasi_to_pengeluaran_unit_details_table	13
55	2026_11_16_004815_add_qty_keluar_rekonsiliasi_to_pengeluaran_unit_details_table	14
56	2026_11_16_033615_convert_legacy_pengeluaran_numbers_to_out_format	15
57	2026_11_16_061123_add_reconciliation_fields_to_penerimaan_pemindahan_units_table	16
58	2026_11_16_145248_create_stok_opnames_table	17
\.


--
-- Name: migrations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.migrations_id_seq', 58, true);


--
-- PostgreSQL database dump complete
--

\unrestrict bNLo4Ngupdeps3IaP9eEEx4hetvkQDd40wAeFJlTAAqnLALY4EoHob24lQ6clzg

