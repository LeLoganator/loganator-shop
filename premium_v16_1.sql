-- LOGANATOR SHOP V16.1 — PREMIUM BACKEND / FIX
-- Idempotent migration. Run the whole file once in Supabase SQL Editor.
-- V16.1.1 compatibility fix: product_id columns do not require products.id to be UNIQUE.
-- This keeps compatibility with existing LOGANATOR SHOP products tables.
create extension if not exists pgcrypto;

-- Existing products: add optional Premium fields safely.
alter table public.products add column if not exists stock integer not null default 0;
alter table public.products add column if not exists sizes text[] not null default '{}';
alter table public.products add column if not exists badge text;
alter table public.products add column if not exists is_featured boolean not null default false;
alter table public.products add column if not exists is_popular boolean not null default false;
alter table public.products add column if not exists is_new boolean not null default true;
alter table public.products add column if not exists is_published boolean not null default true;
alter table public.products add column if not exists publish_at timestamptz;
alter table public.products add column if not exists compare_at_price numeric;

create table if not exists public.premium_admins (email text primary key, created_at timestamptz not null default now());
insert into public.premium_admins(email) values ('gauducheaulogan@gmail.com'),('leloganator@gmail.com'),('alexisprouillac@gmail.com'),('theteamsloganator@gmail.com') on conflict (email) do nothing;

create table if not exists public.premium_members (email text primary key, created_at timestamptz not null default now());
insert into public.premium_members(email) select email from public.premium_admins on conflict (email) do nothing;

create table if not exists public.product_meta (
 product_id uuid primary key,
 stock integer not null default 0,
 sizes text[] not null default '{}', badge text,
 is_featured boolean not null default false,
 is_popular boolean not null default false,
 is_new boolean not null default true,
 is_published boolean not null default true,
 publish_at timestamptz, compare_at_price numeric,
 updated_at timestamptz not null default now());

create table if not exists public.store_settings (
 id integer primary key default 1 check(id=1),
 published jsonb not null default '{}'::jsonb,
 draft jsonb not null default '{}'::jsonb,
 updated_at timestamptz not null default now());
insert into public.store_settings(id) values(1) on conflict(id) do nothing;

create table if not exists public.promo_codes (
 id uuid primary key default gen_random_uuid(), code text unique not null,
 type text not null check(type in ('percent','fixed')), value numeric not null check(value>=0),
 starts_at timestamptz, ends_at timestamptz, max_uses integer, used_count integer not null default 0,
 min_order numeric not null default 0, active boolean not null default true, created_at timestamptz not null default now());

create table if not exists public.orders (
 id uuid primary key default gen_random_uuid(), user_id uuid references auth.users(id) on delete set null,
 email text, status text not null default 'pending' check(status in ('pending','preparation','shipped','delivered','cancelled')),
 subtotal numeric not null default 0, shipping numeric not null default 0, discount numeric not null default 0, total numeric not null default 0,
 shipping_info jsonb not null default '{}'::jsonb, payment_status text not null default 'unpaid' check(payment_status in ('unpaid','pending','paid','failed','refunded')),
 payment_provider text, created_at timestamptz not null default now());

create table if not exists public.order_items (
 id uuid primary key default gen_random_uuid(), order_id uuid not null references public.orders(id) on delete cascade,
 product_id uuid, product_name text not null, price numeric not null,
 quantity integer not null check(quantity>0), size text, created_at timestamptz not null default now());

create table if not exists public.analytics_events (
 id bigint generated always as identity primary key,
 event_type text not null check(event_type in ('visit','product_view','favorite','cart_add','checkout_start','order')),
 product_id uuid, session_id text, path text,
 metadata jsonb not null default '{}'::jsonb, created_at timestamptz not null default now());

create table if not exists public.premium_audit (
 id bigint generated always as identity primary key, email text, action text not null, entity text, entity_id text,
 metadata jsonb not null default '{}'::jsonb, created_at timestamptz not null default now());

create or replace function public.is_premium() returns boolean language sql stable security definer set search_path=public as $$
 select exists(select 1 from public.premium_members pm where lower(pm.email)=lower(coalesce(auth.jwt()->>'email','')));
$$;
create or replace function public.is_premium_owner() returns boolean language sql stable security definer set search_path=public as $$
 select exists(select 1 from public.premium_admins pa where lower(pa.email)=lower(coalesce(auth.jwt()->>'email','')));
$$;

alter table public.premium_admins enable row level security;
alter table public.premium_members enable row level security;
alter table public.product_meta enable row level security;
alter table public.store_settings enable row level security;
alter table public.promo_codes enable row level security;
alter table public.orders enable row level security;
alter table public.order_items enable row level security;
alter table public.analytics_events enable row level security;
alter table public.premium_audit enable row level security;
alter table public.products enable row level security;

-- Remove only policies created by this migration so it can be rerun safely.
do $$ declare r record; begin
 for r in select policyname,tablename from pg_policies where schemaname='public' and policyname like 'v16_1_%' loop
   execute format('drop policy if exists %I on public.%I',r.policyname,r.tablename);
 end loop;
end $$;

create policy v16_1_admins_select on public.premium_admins for select to authenticated using(public.is_premium_owner());
create policy v16_1_members_select on public.premium_members for select to authenticated using(lower(email)=lower(coalesce(auth.jwt()->>'email','')) or public.is_premium_owner());
create policy v16_1_members_manage on public.premium_members for all to authenticated using(public.is_premium_owner()) with check(public.is_premium_owner());
create policy v16_1_meta_read on public.product_meta for select to anon,authenticated using(is_published=true or public.is_premium());
create policy v16_1_meta_manage on public.product_meta for all to authenticated using(public.is_premium()) with check(public.is_premium());
create policy v16_1_settings_manage on public.store_settings for all to authenticated using(public.is_premium()) with check(public.is_premium());
create policy v16_1_promos_read on public.promo_codes for select to anon,authenticated using(active=true and (starts_at is null or starts_at<=now()) and (ends_at is null or ends_at>=now()));
create policy v16_1_promos_manage on public.promo_codes for all to authenticated using(public.is_premium()) with check(public.is_premium());
create policy v16_1_orders_owner on public.orders for select to authenticated using(user_id=auth.uid() or lower(email)=lower(coalesce(auth.jwt()->>'email','')));
create policy v16_1_orders_premium_read on public.orders for select to authenticated using(public.is_premium());
create policy v16_1_orders_insert on public.orders for insert to authenticated with check(user_id=auth.uid() or user_id is null);
create policy v16_1_orders_premium_update on public.orders for update to authenticated using(public.is_premium()) with check(public.is_premium());
create policy v16_1_items_owner on public.order_items for select to authenticated using(exists(select 1 from public.orders o where o.id=order_id and (o.user_id=auth.uid() or lower(o.email)=lower(coalesce(auth.jwt()->>'email','')))));
create policy v16_1_items_premium on public.order_items for select to authenticated using(public.is_premium());
create policy v16_1_items_insert on public.order_items for insert to authenticated with check(exists(select 1 from public.orders o where o.id=order_id and (o.user_id=auth.uid() or o.user_id is null)));
create policy v16_1_analytics_insert on public.analytics_events for insert to anon,authenticated with check(true);
create policy v16_1_analytics_read on public.analytics_events for select to authenticated using(public.is_premium());
create policy v16_1_audit_read on public.premium_audit for select to authenticated using(public.is_premium());
create policy v16_1_products_read on public.products for select to anon,authenticated using(true);
create policy v16_1_products_insert on public.products for insert to authenticated with check(public.is_premium());
create policy v16_1_products_update on public.products for update to authenticated using(public.is_premium()) with check(public.is_premium());
create policy v16_1_products_delete on public.products for delete to authenticated using(public.is_premium());

create index if not exists idx_v16_1_products_category on public.products(category);
create index if not exists idx_v16_1_products_created on public.products(created_at desc);
create index if not exists idx_v16_1_orders_created on public.orders(created_at desc);
create index if not exists idx_v16_1_orders_status on public.orders(status);
create index if not exists idx_v16_1_analytics_type_created on public.analytics_events(event_type,created_at desc);
create index if not exists idx_v16_1_analytics_product_created on public.analytics_events(product_id,created_at desc);
create index if not exists idx_v16_1_product_meta_published on public.product_meta(is_published);

-- Storage bucket used by the existing product image uploader.
insert into storage.buckets(id,name,public) values('product-images','product-images',true) on conflict(id) do update set public=true;
