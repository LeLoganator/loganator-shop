-- LOGANATOR SHOP — gestion des membres PREMIUM
create table if not exists public.premium_members (
  id uuid primary key default gen_random_uuid(),
  email text not null unique,
  created_at timestamptz not null default now()
);

alter table public.premium_members enable row level security;

-- Les 4 propriétaires actuels peuvent voir tous les membres PREMIUM.
-- Un membre PREMIUM normal peut voir uniquement sa propre ligne.
create policy "premium members select" on public.premium_members
for select to authenticated
using (
  lower(email) = lower((select auth.jwt() ->> 'email'))
  or lower((select auth.jwt() ->> 'email')) in (
    'gauducheaulogan@gmail.com',
    'leloganator@gmail.com',
    'alexisprouillac@gmail.com',
    'theteamsloganator@gmail.com'
  )
);

-- Seuls les 4 propriétaires peuvent ajouter/modifier/supprimer des membres.
create policy "premium members insert owners" on public.premium_members
for insert to authenticated
with check (
  lower((select auth.jwt() ->> 'email')) in (
    'gauducheaulogan@gmail.com',
    'leloganator@gmail.com',
    'alexisprouillac@gmail.com',
    'theteamsloganator@gmail.com'
  )
);

create policy "premium members update owners" on public.premium_members
for update to authenticated
using (
  lower((select auth.jwt() ->> 'email')) in (
    'gauducheaulogan@gmail.com',
    'leloganator@gmail.com',
    'alexisprouillac@gmail.com',
    'theteamsloganator@gmail.com'
  )
)
with check (
  lower((select auth.jwt() ->> 'email')) in (
    'gauducheaulogan@gmail.com',
    'leloganator@gmail.com',
    'alexisprouillac@gmail.com',
    'theteamsloganator@gmail.com'
  )
);

create policy "premium members delete owners" on public.premium_members
for delete to authenticated
using (
  lower((select auth.jwt() ->> 'email')) in (
    'gauducheaulogan@gmail.com',
    'leloganator@gmail.com',
    'alexisprouillac@gmail.com',
    'theteamsloganator@gmail.com'
  )
);

-- Les 4 propriétaires sont automatiquement PREMIUM.
insert into public.premium_members (email)
values
  ('gauducheaulogan@gmail.com'),
  ('leloganator@gmail.com'),
  ('alexisprouillac@gmail.com'),
  ('theteamsloganator@gmail.com')
on conflict (email) do nothing;
