-- LOGANATOR SHOP V16 — PREMIUM BACKEND
-- À exécuter dans Supabase SQL Editor.
-- Utilise uniquement la clé publishable côté navigateur. Ne mets jamais de service-role key dans index.html.

create extension if not exists pgcrypto;

-- Métadonnées avancées des produits
alter table public.products add column if not exists stock integer not null default 0;
alter table public.products add column if not exists sizes text[] not null default '{}';
alter table public.products add column if not exists badge text;
alter table public.products add column if not exists is_featured boolean not null default false;
alter table public.products add column if not exists is_popular boolean not null default false;
alter table public.products add column if not exists is_new boolean not null default true;
alter table public.products add column if not exists is_published boolean not null default true;
alter table public.products add column if not exists publish_at timestamptz;
alter table public.products add column if not exists compare_at_price numeric;

-- Accès Premium principal
create table if not exists public.premium_admins (
  email text primary key,
  created_at timestamptz not null default now()
);

insert into public.premium_admins(email) values
 ('gauducheaulogan@gmail.com'),
 ('leloganator@gmail.com'),
 ('alexisprouillac@gmail.com'),
 ('theteamsloganator@gmail.com')
on conflict (email) do nothing;

-- S'assure que les comptes principaux sont Premium
insert into public.premium_members(email)
select pa.email from public.premium_admins pa
where not exists (select 1 from public.premium_members pm where lower(pm.email)=lower(pa.email));

-- Métadonnées avancées (tailles, stock, publication, etc.)
create table if not exists public.product_meta (
  product_id uuid primary key references public.products(id) on delete cascade,
  stock integer not null default 0,
  sizes text[] not null default '{}',
  badge text,
  is_featured boolean not null default false,
  is_popular boolean not null default false,
  is_new boolean not null default true,
  is_published boolean not null default true,
  publish_at timestamptz,
  compare_at_price numeric,
  updated_at timestamptz not null default now()
);

-- Paramètres du site et brouillons
create table if not exists public.store_settings (
  id integer primary key default 1 check (id = 1),
  published jsonb not null default '{}'::jsonb,
  draft jsonb not null default '{}'::jsonb,
  updated_at timestamptz not null default now()
);
insert into public.store_settings(id) values (1) on conflict (id) do nothing;

-- Codes promo
create table if not exists public.promo_codes (
  id uuid primary key default gen_random_uuid(),
  code text unique not null,
  type text not null check (type in ('percent','fixed')),
  value numeric not null check (value >= 0),
  starts_at timestamptz,
  ends_at timestamptz,
  max_uses integer,
  used_count integer not null default 0,
  min_order numeric not null default 0,
  active boolean not null default true,
  created_at timestamptz not null default now()
);

-- Commandes (paiement réel volontairement séparé)
create table if not exists public.orders (
  id uuid primary key default gen_random_uuid(),
  user_id uuid references auth.users(id) on delete set null,
  email text,
  status text not null default 'pending' check (status in ('pending','preparation','shipped','delivered','cancelled')),
  subtotal numeric not null default 0,
  shipping numeric not null default 0,
  discount numeric not null default 0,
  total numeric not null default 0,
  shipping_info jsonb not null default '{}'::jsonb,
  payment_status text not null default 'unpaid' check (payment_status in ('unpaid','pending','paid','failed','refunded')),
  payment_provider text,
  created_at timestamptz not null default now()
);

create table if not exists public.order_items (
  id uuid primary key default gen_random_uuid(),
  order_id uuid not null references public.orders(id) on delete cascade,
  product_id uuid references public.products(id) on delete set null,
  product_name text not null,
  price numeric not null,
  quantity integer not null check (quantity > 0),
  size text,
  created_at timestamptz not null default now()
);

-- Analytics anonymisées / fonctionnelles pour le dashboard
create table if not exists public.analytics_events (
  id bigint generated always as identity primary key,
  event_type text not null check (event_type in ('visit','product_view','favorite','cart_add','checkout_start','order')),
  product_id uuid references public.products(id) on delete set null,
  session_id text,
  path text,
  metadata jsonb not null default '{}'::jsonb,
  created_at timestamptz not null default now()
);

-- Journal Premium
create table if not exists public.premium_audit (
  id bigint generated always as identity primary key,
  email text,
  action text not null,
  entity text,
  entity_id text,
  metadata jsonb not null default '{}'::jsonb,
  created_at timestamptz not null default now()
);

-- Fonctions de sécurité. SECURITY DEFINER évite de contourner les règles via le navigateur.
create or replace function public.is_premium()
returns boolean
language sql
stable
security definer
set search_path = public
as $$
  select exists (
    select 1 from public.premium_members pm
    where lower(pm.email) = lower(coalesce(auth.jwt()->>'email',''))
  );
$$;

create or replace function public.is_premium_owner()
returns boolean
language sql
stable
security definer
set search_path = public
as $$
  select exists (
    select 1 from public.premium_admins pa
    where lower(pa.email) = lower(coalesce(auth.jwt()->>'email',''))
  );
$$;

-- RLS
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

-- Policies nommées V16. Elles sont additives : si ton projet possède d'anciennes policies trop permissives, supprime-les avant production.
drop policy if exists v16_premium_admins_select on public.premium_admins;
drop policy if exists v16_premium_members_self on public.premium_members;
drop policy if exists v16_premium_members_owner on public.premium_members;
drop policy if exists v16_product_meta_public_select on public.product_meta;
drop policy if exists v16_product_meta_premium_write on public.product_meta;
drop policy if exists v16_settings_premium on public.store_settings;
drop policy if exists v16_promo_public_read on public.promo_codes;
drop policy if exists v16_promo_premium_write on public.promo_codes;
drop policy if exists v16_orders_owner_read on public.orders;
drop policy if exists v16_orders_premium_read on public.orders;
drop policy if exists v16_orders_auth_insert on public.orders;
drop policy if exists v16_orders_premium_update on public.orders;
drop policy if exists v16_order_items_owner_read on public.order_items;
drop policy if exists v16_order_items_premium_read on public.order_items;
drop policy if exists v16_order_items_auth_insert on public.order_items;
drop policy if exists v16_analytics_insert on public.analytics_events;
drop policy if exists v16_analytics_premium_read on public.analytics_events;
drop policy if exists v16_audit_premium_read on public.premium_audit;
drop policy if exists v16_products_public_select on public.products;
drop policy if exists v16_products_premium_insert on public.products;
drop policy if exists v16_products_premium_update on public.products;
drop policy if exists v16_products_premium_delete on public.products;

create policy v16_premium_admins_select on public.premium_admins for select to authenticated using (public.is_premium_owner());
create policy v16_premium_members_self on public.premium_members for select to authenticated using (lower(email)=lower(coalesce(auth.jwt()->>'email','')) or public.is_premium_owner());
create policy v16_premium_members_owner on public.premium_members for all to authenticated using (public.is_premium_owner()) with check (public.is_premium_owner());

create policy v16_product_meta_public_select on public.product_meta for select to anon, authenticated using (is_published = true or public.is_premium());
create policy v16_product_meta_premium_write on public.product_meta for all to authenticated using (public.is_premium()) with check (public.is_premium());

create policy v16_settings_premium on public.store_settings for all to authenticated using (public.is_premium()) with check (public.is_premium());

create policy v16_promo_public_read on public.promo_codes for select to anon, authenticated using (active = true and (starts_at is null or starts_at <= now()) and (ends_at is null or ends_at >= now()));
create policy v16_promo_premium_write on public.promo_codes for all to authenticated using (public.is_premium()) with check (public.is_premium());

create policy v16_orders_owner_read on public.orders for select to authenticated using (user_id = auth.uid() or lower(email)=lower(coalesce(auth.jwt()->>'email','')));
create policy v16_orders_premium_read on public.orders for select to authenticated using (public.is_premium());
create policy v16_orders_auth_insert on public.orders for insert to authenticated with check (user_id = auth.uid() or user_id is null);
create policy v16_orders_premium_update on public.orders for update to authenticated using (public.is_premium()) with check (public.is_premium());

create policy v16_order_items_owner_read on public.order_items for select to authenticated using (exists(select 1 from public.orders o where o.id=order_id and (o.user_id=auth.uid() or lower(o.email)=lower(coalesce(auth.jwt()->>'email','')))));
create policy v16_order_items_premium_read on public.order_items for select to authenticated using (public.is_premium());
create policy v16_order_items_auth_insert on public.order_items for insert to authenticated with check (exists(select 1 from public.orders o where o.id=order_id and (o.user_id=auth.uid() or o.user_id is null)));

create policy v16_analytics_insert on public.analytics_events for insert to anon, authenticated with check (true);
create policy v16_analytics_premium_read on public.analytics_events for select to authenticated using (public.is_premium());
create policy v16_audit_premium_read on public.premium_audit for select to authenticated using (public.is_premium());

-- Produits : lecture publique et écritures Premium. Les policies existantes trop permissives doivent être retirées.
create policy v16_products_public_select on public.products for select to anon, authenticated using (true);
create policy v16_products_premium_insert on public.products for insert to authenticated with check (public.is_premium());
create policy v16_products_premium_update on public.products for update to authenticated using (public.is_premium()) with check (public.is_premium());
create policy v16_products_premium_delete on public.products for delete to authenticated using (public.is_premium());

-- Index utiles
create index if not exists idx_product_meta_published on public.product_meta(is_published);
create index if not exists idx_products_category on public.products(category);
create index if not exists idx_products_created_at on public.products(created_at desc);
create index if not exists idx_orders_created_at on public.orders(created_at desc);
create index if not exists idx_orders_status on public.orders(status);
create index if not exists idx_analytics_type_created on public.analytics_events(event_type, created_at desc);
create index if not exists idx_analytics_product_created on public.analytics_events(product_id, created_at desc);
create index if not exists idx_promo_code on public.promo_codes(code);

-- Remplissage initial des métadonnées depuis products
insert into public.product_meta(product_id, stock, sizes, badge, is_featured, is_popular, is_new, is_published, compare_at_price)
select id, coalesce(stock,0), coalesce(sizes,'{}'), badge, coalesce(is_featured,false), coalesce(is_popular,false), coalesce(is_new,true), coalesce(is_published,true), compare_at_price
from public.products
on conflict (product_id) do nothing;
